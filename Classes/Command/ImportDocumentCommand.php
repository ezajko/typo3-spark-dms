<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Command;

use EtfUnsa\SparkDms\Domain\Model\Document;
use EtfUnsa\SparkDms\Domain\Model\DocumentVersion;
use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentCategoryRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentTypeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Console command to import documents into DMS
 * 
 * Can create new documents or add versions to existing ones.
 * Uses the same protected storage logic as backend uploads.
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
#[AsCommand(
    name: 'dms:import',
    description: 'Import a new document or add a version to an existing document',
)]
class ImportDocumentCommand extends Command
{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly DocumentCategoryRepository $categoryRepository,
        protected readonly DocumentTypeRepository $typeRepository,
        protected readonly PersistenceManager $persistenceManager,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly StorageRepository $storageRepository,
        protected readonly SiteFinder $siteFinder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Import a document file into DMS. Creates a new document or adds a version to an existing one.')
            // Required option
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to the file to import'
            )
            // Options for new document
            ->addOption(
                'title',
                't',
                InputOption::VALUE_REQUIRED,
                'Document title (required for new documents)'
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Document type UID'
            )
            ->addOption(
                'category',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Document category UID (can be repeated for multiple categories)'
            )
            ->addOption(
                'registry-number',
                'r',
                InputOption::VALUE_REQUIRED,
                'Registry number'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_REQUIRED,
                'Document date (Y-m-d format)'
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_REQUIRED,
                'Document description'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'Storage page ID (default: from site settings)'
            )
            // Options for adding version
            ->addOption(
                'document',
                null,
                InputOption::VALUE_REQUIRED,
                'Existing document UID to add a new version to'
            )
            ->addOption(
                'version-comment',
                null,
                InputOption::VALUE_REQUIRED,
                'Comment for the version'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get file path
        $filePath = $input->getOption('file');
        if (empty($filePath)) {
            $io->error('The --file option is required.');
            return Command::FAILURE;
        }

        // Validate file exists
        if (!file_exists($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        // Determine mode: new document or add version
        $documentUid = $input->getOption('document');
        
        if ($documentUid !== null) {
            return $this->addVersionToDocument($io, $input, (int)$documentUid, $filePath);
        }
        
        return $this->createNewDocument($io, $input, $filePath);
    }

    /**
     * Create a new document with first version
     */
    protected function createNewDocument(SymfonyStyle $io, InputInterface $input, string $filePath): int
    {
        $io->section('Creating new document');

        // Title is required for new documents
        $title = $input->getOption('title');
        if (empty($title)) {
            $io->error('The --title option is required for new documents.');
            return Command::FAILURE;
        }

        // Get PID from option or site settings
        $pid = $this->resolvePid($input->getOption('pid'));
        if ($pid <= 0) {
            $io->error('Could not determine storage PID. Use --pid option or configure sparkdms.storagePid in site settings.');
            return Command::FAILURE;
        }

        try {
            // Create document entity
            $document = new Document();
            $document->setPid($pid);
            $document->setTitle($title);
            $document->setUuid($this->generateUuid());
            
            // Optional: registry number
            if ($registryNumber = $input->getOption('registry-number')) {
                $document->setRegistryNumber($registryNumber);
            }

            // Optional: document date
            if ($dateString = $input->getOption('date')) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateString);
                if ($date) {
                    $document->setDocumentDate($date);
                } else {
                    $io->warning('Invalid date format. Expected Y-m-d. Using current date.');
                    $document->setDocumentDate(new \DateTime());
                }
            } else {
                $document->setDocumentDate(new \DateTime());
            }

            // Optional: description
            if ($description = $input->getOption('description')) {
                $document->setDescription($description);
            }

            // Optional: type
            if ($typeUid = $input->getOption('type')) {
                $type = $this->typeRepository->findByUid((int)$typeUid);
                if ($type) {
                    $document->setType($type);
                } else {
                    $io->warning(sprintf('Document type UID %d not found. Skipping.', $typeUid));
                }
            }

            // Optional: categories
            $categoryUids = $input->getOption('category');
            foreach ($categoryUids as $categoryUid) {
                $category = $this->categoryRepository->findByUid((int)$categoryUid);
                if ($category) {
                    $document->addCategory($category);
                } else {
                    $io->warning(sprintf('Category UID %d not found. Skipping.', $categoryUid));
                }
            }

            // Upload file and create version
            $version = $this->createVersionWithFile($filePath, $document, $pid, '1.0');
            $document->addVersion($version);

            // Persist
            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            // Output success
            $io->success('Document created successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['UID', (string)$document->getUid()],
                    ['UUID', $document->getUuid()],
                    ['Title', $document->getTitle()],
                    ['Registry #', $document->getRegistryNumber() ?: '-'],
                    ['Type', $document->getType() ? $document->getType()->getTitle() : '-'],
                    ['Version', $version->getVersionLabel()],
                    ['File', basename($filePath)],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Failed to create document: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * Add a new version to an existing document
     */
    protected function addVersionToDocument(SymfonyStyle $io, InputInterface $input, int $documentUid, string $filePath): int
    {
        $io->section(sprintf('Adding version to document UID %d', $documentUid));

        // Find existing document
        $document = $this->documentRepository->findByUid($documentUid);
        if (!$document) {
            $io->error(sprintf('Document with UID %d not found.', $documentUid));
            return Command::FAILURE;
        }

        try {
            // Calculate next version label
            $latestVersion = $document->getLatestVersion();
            $nextVersionLabel = $this->calculateNextVersionLabel($latestVersion);

            // Use version comment as label if provided
            $versionComment = $input->getOption('version-comment');
            if (!empty($versionComment)) {
                $nextVersionLabel = $nextVersionLabel . ' - ' . $versionComment;
            }

            // Create new version
            $version = $this->createVersionWithFile($filePath, $document, $document->getPid(), $nextVersionLabel);
            $document->addVersion($version);

            // Persist
            $this->persistenceManager->persistAll();

            // Output success
            $io->success('Version added successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Document UID', (string)$document->getUid()],
                    ['Document Title', $document->getTitle()],
                    ['New Version', $version->getVersionLabel()],
                    ['Version UUID', $version->getUuid()],
                    ['File', basename($filePath)],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Failed to add version: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * Create a DocumentVersion and upload file to protected storage
     */
    protected function createVersionWithFile(string $filePath, Document $document, int $pid, string $versionLabel): DocumentVersion
    {
        // Get protected storage
        $storageUid = $this->getProtectedStorageUid();
        if ($storageUid <= 0) {
            throw new \RuntimeException('Protected storage not configured. Set sparkdms.protectedStorageUid in site settings.');
        }

        $storage = $this->storageRepository->findByUid($storageUid);
        if (!$storage) {
            throw new \RuntimeException(sprintf('Storage with UID %d not found.', $storageUid));
        }

        // Ensure /_inbox/ folder exists
        $inboxPath = '/_inbox/';
        if (!$storage->hasFolder($inboxPath)) {
            $storage->createFolder('_inbox', $storage->getRootLevelFolder());
        }
        $inboxFolder = $storage->getFolder($inboxPath);

        // Upload file
        $fileName = basename($filePath);
        $file = $storage->addFile($filePath, $inboxFolder, $fileName);

        // Create FileReference
        $fileReference = $this->resourceFactory->createFileReferenceObject([
            'uid_local' => $file->getUid(),
            'uid_foreign' => 0, // Will be set after persist
            'tablenames' => 'tx_sparkdms_domain_model_document_version',
            'fieldname' => 'file',
        ]);

        // Create Extbase FileReference
        $extbaseFileReference = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Domain\Model\FileReference::class);
        $extbaseFileReference->setOriginalResource($fileReference);

        // Create version entity
        $version = new DocumentVersion();
        $version->setPid($pid);
        $version->setVersionLabel($versionLabel);
        $version->setUuid($this->generateUuid());
        $version->setFile($extbaseFileReference);
        $version->setDocument($document);

        return $version;
    }

    /**
     * Get protected storage UID from site settings
     */
    protected function getProtectedStorageUid(): int
    {
        try {
            $sites = $this->siteFinder->getAllSites();
            foreach ($sites as $site) {
                $settings = $site->getSettings();
                $storageUid = $settings->get('sparkdms.protectedStorageUid', 0);
                if ($storageUid > 0) {
                    return (int)$storageUid;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return 0;
    }

    /**
     * Resolve PID from option or site settings
     */
    protected function resolvePid(?string $pidOption): int
    {
        if ($pidOption !== null) {
            return (int)$pidOption;
        }

        try {
            $sites = $this->siteFinder->getAllSites();
            foreach ($sites as $site) {
                $settings = $site->getSettings();
                $storagePid = $settings->get('sparkdms.storagePid', 0);
                if ($storagePid > 0) {
                    return (int)$storagePid;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 0;
    }

    /**
     * Calculate next version label based on latest version
     */
    protected function calculateNextVersionLabel(?DocumentVersion $latestVersion): string
    {
        if ($latestVersion === null) {
            return '1.0';
        }

        $currentLabel = $latestVersion->getVersionLabel();
        
        // Try to parse version number (e.g., "1.0", "2.0")
        if (preg_match('/^(\d+)\.(\d+)/', $currentLabel, $matches)) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            return sprintf('%d.%d', $major, $minor + 1);
        }

        // Fallback: just increment
        return '2.0';
    }

    /**
     * Generate a UUID v4
     */
    protected function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
