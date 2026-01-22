# Spark DMS for TYPO3

A robust Document Management System (DMS) extension for TYPO3 CMS 13 LTS and v14.

## Features

- **Document Management**: Manage documents with titles, registry numbers, types, and categories.
- **Versioning**: Automatic version control with file history and "V.x" labeling.
- **Categorization**: Hierarchical categories and document types.
- **Frontend Plugins**:
  - **List/Download**: Filterable, paginated list of documents.
  - **Detail View**: Full document history and details.
- **Secure Downloads**: File abstraction layer for secure document delivery.
- **Bidirectional Relations**: Link documents to each other with automatic back-referencing.
- **Localization**: Full support for English (en) and Bosnian (bs).
- **CLI Commands**: Import/Export documents via command line.
- **Frontend Filtering**: Advanced filtering by type, category, date, and keyword.

## Requirements

- TYPO3 v13.4+ or v14.0+
- PHP 8.2+

## Installation

```bash
composer require rootba/typo3-spark-dms
```

## Configuration

1. **Include TypoScript**: Add "Spark DMS" static templates to your site.
2. **Storage PID**: Configure the storage PID in Site Settings or TypoScript constant `plugin.tx_sparkdms_pi1.persistence.storagePid`.
3. **Routing**: Add the route configuration from `Configuration/Routes/Default.yaml` (automatically handled in v13).

## Documentation

Full documentation is available in the `Documentation` folder of this extension.

## License

GPL-2.0-or-later
