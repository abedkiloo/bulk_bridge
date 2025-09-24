import React, { useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import './FileUpload.css';

const FileUpload = ({ onFileSelect, isUploading, uploadProgress = 0, selectedFile = null }) => {
  const [dragActive, setDragActive] = useState(false);

  const onDrop = useCallback((acceptedFiles) => {
    if (acceptedFiles.length > 0) {
      const file = acceptedFiles[0];
      // Validate file type
      if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
        onFileSelect(file);
      } else {
        alert('Please select a CSV file');
      }
    }
  }, [onFileSelect]);

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    accept: {
      'text/csv': ['.csv']
    },
    multiple: false,
    disabled: isUploading
  });

  return (
    <div className="file-upload-container">
      <div
        {...getRootProps()}
        className={`file-upload-zone ${isDragActive ? 'drag-active' : ''} ${isUploading ? 'uploading' : ''}`}
      >
        <input {...getInputProps()} />
        <div className="upload-content">
        {isUploading ? (
          <div className="uploading-state">
            <div className="upload-progress-container">
              <div className="upload-progress-bar">
                <div 
                  className="upload-progress-fill"
                  style={{ width: `${uploadProgress}%` }}
                />
              </div>
              <div className="upload-progress-text">
                <span className="upload-percentage">{Math.round(uploadProgress)}%</span>
                <span className="upload-status">
                  {selectedFile ? `Uploading ${selectedFile.name}...` : 'Uploading file...'}
                </span>
              </div>
              {selectedFile && (
                <div className="file-info">
                  <span className="file-size">
                    {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                  </span>
                </div>
              )}
            </div>
          </div>
        ) : (
            <div className="upload-prompt">
              <div className="upload-icon">📁</div>
              <h3>Upload CSV File</h3>
              <p>
                {isDragActive
                  ? 'Drop your CSV file here'
                  : 'Drag & drop your CSV file here, or click to select'}
              </p>
              <div className="file-requirements">
                <small>
                  Supported format: CSV files only<br />
                  Max size: 20MB
                </small>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default FileUpload;
