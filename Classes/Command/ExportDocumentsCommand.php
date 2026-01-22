<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Console command to export DMS documents to CSV or JSON
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
#[AsCommand(
    name: 'dms:export',
    description: 'Export DMS documents to CSV or JSON file',
)]
class ExportDocumentsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('Export documents from the DMS to a CSV or JSON file.')
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
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path (default: stdout)'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Export format: csv or json',
                'csv'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get options
        $typeUid = $input->getOption('type');
        $categoryUid = $input->getOption('category');
        $outputPath = $input->getOption('output');
        $format = strtolower($input->getOption('format'));

        if (!in_array($format, ['csv', 'json'])) {
            $io->error('Invalid format. Use "csv" or "json".');
            return Command::FAILURE;
        }

        $io->section('Exporting documents');

        // Build query
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document');

        $queryBuilder
            ->select(
                'd.uid',
                'd.title',
                'd.registry_number',
                'd.uuid',
                'd.document_date',
                'd.description',
                'd.is_protected',
                't.title AS type_title',
                't.slug AS type_slug'
            )
            ->from('tx_sparkdms_domain_model_document', 'd')
            ->leftJoin(
                'd',
                'tx_sparkdms_domain_model_document_type',
                't',
                $queryBuilder->expr()->eq('d.type', $queryBuilder->quoteIdentifier('t.uid'))
            )
            ->where($queryBuilder->expr()->eq('d.deleted', 0))
            ->orderBy('d.document_date', 'DESC');

        // Apply filters
        if ($typeUid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('d.type', $queryBuilder->createNamedParameter((int)$typeUid))
            );
        }

        if ($categoryUid !== null) {
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

        // Execute query
        $documents = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (empty($documents)) {
            $io->warning('No documents found matching the criteria.');
            return Command::SUCCESS;
        }

        // Add categories and version info
        $this->enrichDocumentData($documents);

        // Format data
        $exportData = $format === 'json' 
            ? $this->formatJson($documents) 
            : $this->formatCsv($documents);

        // Output
        if ($outputPath) {
            file_put_contents($outputPath, $exportData);
            $io->success(sprintf('Exported %d documents to %s', count($documents), $outputPath));
        } else {
            $output->write($exportData);
        }

        return Command::SUCCESS;
    }

    /**
     * Enrich documents with categories and latest version info
     */
    protected function enrichDocumentData(array &$documents): void
    {
        foreach ($documents as &$doc) {
            // Get categories
            $doc['categories'] = $this->getDocumentCategories($doc['uid']);
            
            // Get latest version info
            $latestVersion = $this->getLatestVersion($doc['uid']);
            $doc['latest_version'] = $latestVersion['version_label'] ?? '';
            $doc['latest_version_date'] = $latestVersion['created_at'] ?? 0;
            
            // Count versions
            $doc['version_count'] = $this->getVersionCount($doc['uid']);
            
            // Format date
            $doc['document_date_formatted'] = $doc['document_date'] > 0 
                ? date('Y-m-d', $doc['document_date']) 
                : '';
        }
    }

    /**
     * Get categories for a document
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
     * Get latest version of a document
     */
    protected function getLatestVersion(int $documentUid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_version');

        return $queryBuilder
            ->select('version_label', 'created_at')
            ->from('tx_sparkdms_domain_model_document_version')
            ->where(
                $queryBuilder->expr()->eq('document', $queryBuilder->createNamedParameter($documentUid))
            )
            ->orderBy('created_at', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    /**
     * Get version count
     */
    protected function getVersionCount(int $documentUid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_version');

        return (int)$queryBuilder
            ->count('uid')
            ->from('tx_sparkdms_domain_model_document_version')
            ->where(
                $queryBuilder->expr()->eq('document', $queryBuilder->createNamedParameter($documentUid))
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Format as JSON
     */
    protected function formatJson(array $documents): string
    {
        $exportData = array_map(function($doc) {
            return [
                'uid' => $doc['uid'],
                'uuid' => $doc['uuid'],
                'title' => $doc['title'],
                'registry_number' => $doc['registry_number'],
                'description' => $doc['description'],
                'document_date' => $doc['document_date_formatted'],
                'type' => $doc['type_title'],
                'categories' => $doc['categories'],
                'is_protected' => (bool)$doc['is_protected'],
                'latest_version' => $doc['latest_version'],
                'version_count' => $doc['version_count'],
            ];
        }, $documents);

        return json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format as CSV
     */
    protected function formatCsv(array $documents): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'uid,uuid,title,registry_number,document_date,type,categories,is_protected,latest_version,version_count';

        // Rows
        foreach ($documents as $doc) {
            $lines[] = sprintf(
                '%d,"%s","%s","%s","%s","%s","%s",%d,"%s",%d',
                $doc['uid'],
                $doc['uuid'],
                str_replace('"', '""', $doc['title']),
                str_replace('"', '""', $doc['registry_number'] ?? ''),
                $doc['document_date_formatted'],
                str_replace('"', '""', $doc['type_title'] ?? ''),
                str_replace('"', '""', $doc['categories']),
                $doc['is_protected'],
                str_replace('"', '""', $doc['latest_version']),
                $doc['version_count']
            );
        }

        return implode("\n", $lines) . "\n";
    }
}
