<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Command;

use RootBa\SparkDms\Domain\Repository\DocumentRepository;
use RootBa\SparkDms\Domain\Repository\DocumentCategoryRepository;
use RootBa\SparkDms\Domain\Repository\DocumentTypeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Console command to list DMS documents
 * 
 * Supports filtering by type, category, and output format.
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
#[AsCommand(
    name: 'dms:list',
    description: 'List DMS documents with optional filtering',
)]
class ListDocumentsCommand extends Command
{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly DocumentCategoryRepository $categoryRepository,
        protected readonly DocumentTypeRepository $typeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('List documents from the DMS with various filter and output options.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Filter by document type UID'
            )
            ->addOption(
                'category',
                'c',
                InputOption::VALUE_REQUIRED,
                'Filter by category UID'
            )
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED,
                'Search in document title'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum number of documents to show',
                '50'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: table, json, csv',
                'table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get options
        $typeUid = $input->getOption('type');
        $categoryUid = $input->getOption('category');
        $search = $input->getOption('search');
        $limit = (int)$input->getOption('limit');
        $format = strtolower($input->getOption('format'));

        // Build query
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document');

        $queryBuilder
            ->select(
                'd.uid',
                'd.pid',
                'd.title',
                'd.registry_number',
                'd.uuid',
                'd.document_date',
                't.title AS type_title'
            )
            ->from('tx_sparkdms_domain_model_document', 'd')
            ->leftJoin(
                'd',
                'tx_sparkdms_domain_model_document_type',
                't',
                $queryBuilder->expr()->eq('d.type', $queryBuilder->quoteIdentifier('t.uid'))
            )
            ->where($queryBuilder->expr()->eq('d.deleted', 0))
            ->orderBy('d.document_date', 'DESC')
            ->setMaxResults($limit);

        // Apply filters
        if ($typeUid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('d.type', $queryBuilder->createNamedParameter((int)$typeUid))
            );
        }

        if ($categoryUid !== null) {
            // Join with MM table for category filter
            $queryBuilder
                ->innerJoin(
                    'd',
                    'tx_sparkdms_document_category_mm',
                    'mm',
                    $queryBuilder->expr()->eq('d.uid', $queryBuilder->quoteIdentifier('mm.uid_local'))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->createNamedParameter((int)$categoryUid))
                );
        }

        if ($search !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like('d.title', $queryBuilder->createNamedParameter('%' . $search . '%'))
            );
        }

        // Execute query
        $documents = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (empty($documents)) {
            $io->info('No documents found.');
            return Command::SUCCESS;
        }

        // Count versions and get categories for each document
        $this->enrichDocumentData($documents);

        // Output based on format
        switch ($format) {
            case 'json':
                $output->writeln(json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'csv':
                $this->outputCsv($output, $documents);
                break;

            case 'table':
            default:
                $this->outputTable($io, $documents);
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Enrich documents with version counts and categories
     */
    protected function enrichDocumentData(array &$documents): void
    {
        foreach ($documents as &$doc) {
            // Version count
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_version');
            
            $count = $queryBuilder
                ->count('uid')
                ->from('tx_sparkdms_domain_model_document_version')
                ->where(
                    $queryBuilder->expr()->eq('document', $queryBuilder->createNamedParameter($doc['uid']))
                )
                ->executeQuery()
                ->fetchOne();
            
            $doc['version_count'] = (int)$count;
            
            // Categories
            $doc['categories'] = $this->getDocumentCategories($doc['uid']);
        }
    }

    /**
     * Get categories for a document as comma-separated string
     */
    protected function getDocumentCategories(int $documentUid): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_category');

        $categories = $queryBuilder
            ->select('c.title')
            ->from('tx_sparkdms_domain_model_document_category', 'c')
            ->innerJoin(
                'c',
                'tx_sparkdms_document_category_mm',
                'mm',
                $queryBuilder->expr()->eq('c.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->createNamedParameter($documentUid))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return implode(', ', array_column($categories, 'title'));
    }

    /**
     * Output as table
     */
    protected function outputTable(SymfonyStyle $io, array $documents): void
    {
        $rows = [];
        foreach ($documents as $doc) {
            $date = $doc['document_date'] > 0 
                ? date('Y-m-d', $doc['document_date']) 
                : '-';
            
            $rows[] = [
                $doc['uid'],
                $doc['pid'],
                substr($doc['uuid'], 0, 8) . '...',
                mb_substr($doc['title'], 0, 30) . (mb_strlen($doc['title']) > 30 ? '...' : ''),
                $doc['registry_number'] ?: '-',
                $doc['type_title'] ?: '-',
                mb_substr($doc['categories'] ?: '-', 0, 20) . (mb_strlen($doc['categories'] ?? '') > 20 ? '...' : ''),
                $date,
                $doc['version_count'],
            ];
        }

        $io->table(
            ['UID', 'PID', 'UUID', 'Title', 'Registry #', 'Type', 'Categories', 'Date', 'Vers'],
            $rows
        );

        $io->text(sprintf('Showing %d document(s)', count($documents)));
    }

    /**
     * Output as CSV
     */
    protected function outputCsv(OutputInterface $output, array $documents): void
    {
        // Header
        $output->writeln('uid,title,registry_number,type,document_date,uuid,version_count');

        // Rows
        foreach ($documents as $doc) {
            $date = $doc['document_date'] > 0 
                ? date('Y-m-d', $doc['document_date']) 
                : '';

            $output->writeln(sprintf(
                '%d,"%s","%s","%s","%s","%s",%d',
                $doc['uid'],
                str_replace('"', '""', $doc['title']),
                str_replace('"', '""', $doc['registry_number'] ?? ''),
                str_replace('"', '""', $doc['type_title'] ?? ''),
                $date,
                $doc['uuid'],
                $doc['version_count']
            ));
        }
    }
}
