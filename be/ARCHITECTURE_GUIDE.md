# 🏗️ System Architecture Guide

## High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   API Layer     │    │   Queue System  │
│   (React/Vue)   │───▶│   Controllers   │───▶│   Jobs          │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
                       ┌─────────────────┐    ┌─────────────────┐
                       │   Services      │    │   Database      │
                       │   (Business     │    │   (PostgreSQL)  │
                       │    Logic)       │    │                 │
                       └─────────────────┘    └─────────────────┘
```

## Core Components

### 1. **API Layer (Controllers)**
- `ImportController` - Handles CSV uploads and import management
- `EmployeeController` - Manages employee CRUD operations

### 2. **Service Layer**
- `CsvParserService` - Parses and validates CSV files
- `EmployeeValidationService` - Validates employee data

### 3. **Job Queue System**
- `ProcessBulkImportJob` - Main import orchestration
- `ProcessImportRowJob` - Processes individual rows

### 4. **Data Models**
- `Employee` - Employee entity
- `ImportJob` - Import tracking
- `ImportRow` - Individual row tracking
- `ImportError` - Error logging

### 5. **Database Layer**
- PostgreSQL with optimized indexes
- Queue tables for job processing
- Audit trail for all operations
