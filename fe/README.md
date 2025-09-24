# BulkBridge Frontend

A React-based frontend application for the BulkBridge employee data import system.

## Features

- 📁 **File Upload**: Drag & drop CSV file upload with validation
- 📊 **Real-time Progress**: Live progress tracking with detailed statistics
- 🎯 **Job Management**: View job status, errors, and completion details
- 📱 **Responsive Design**: Works on desktop and mobile devices
- ⚡ **Real-time Updates**: Auto-refreshing progress bars and status updates

## Getting Started

### Prerequisites

- Node.js (v14 or higher)
- npm or yarn
- Laravel backend running on `http://localhost:8000`

### Installation

1. Install dependencies:
```bash
npm install
```

2. Start the development server:
```bash
npm start
```

3. Open [http://localhost:3000](http://localhost:3000) in your browser.

## Usage

### Upload CSV File

1. Click on the upload area or drag & drop a CSV file
2. The file will be validated and uploaded to the backend
3. An import job will be automatically started

### Monitor Progress

- View real-time progress with a visual progress bar
- See detailed statistics (total, processed, successful, failed rows)
- Monitor job status (pending, processing, completed, failed)

### CSV Format Requirements

Your CSV file must include these columns:
- `employee_number`
- `first_name`
- `last_name`
- `email`
- `department`
- `salary`
- `currency`
- `country_code`
- `start_date`

### Sample CSV Format

```csv
employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date
EMP-12345,John,Doe,john.doe@example.com,Engineering,75000,USD,US,2023-01-15
EMP-12346,Jane,Smith,jane.smith@example.com,Marketing,65000,USD,US,2023-02-01
```

## API Integration

The frontend communicates with the Laravel backend through these endpoints:

- `POST /api/import/upload` - Upload CSV file
- `GET /api/import/job/{jobId}` - Get job status
- `GET /api/import/job/{jobId}/details` - Get detailed job information
- `GET /api/import/job/{jobId}/errors` - Get import errors
- `GET /api/import/job/{jobId}/rows` - Get import rows
- `POST /api/import/job/{jobId}/cancel` - Cancel job
- `POST /api/import/job/{jobId}/retry` - Retry failed job

## Components

### FileUpload
- Drag & drop file upload
- File validation (CSV only)
- Upload progress indication

### ProgressBar
- Visual progress representation
- Detailed statistics display
- Status-based color coding

### JobStatus
- Real-time job monitoring
- Auto-refresh functionality
- Error display and job management

## Styling

The application uses custom CSS with:
- Modern, clean design
- Responsive layout
- Smooth animations and transitions
- Status-based color coding

## Development

### Available Scripts

- `npm start` - Start development server
- `npm build` - Build for production
- `npm test` - Run tests
- `npm eject` - Eject from Create React App

### Project Structure

```
src/
├── components/
│   ├── FileUpload.js
│   ├── FileUpload.css
│   ├── ProgressBar.js
│   ├── ProgressBar.css
│   ├── JobStatus.js
│   └── JobStatus.css
├── services/
│   └── api.js
├── App.js
├── App.css
├── index.js
└── index.css
```

## Troubleshooting

### Common Issues

1. **Backend Connection Error**: Ensure Laravel backend is running on `http://localhost:8000`
2. **CORS Issues**: Check Laravel CORS configuration
3. **File Upload Fails**: Verify file is CSV format and under 20MB
4. **Progress Not Updating**: Check if auto-refresh is enabled

### Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is part of the BulkBridge system.