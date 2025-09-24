import React, { useState } from 'react';
import FileUpload from '../components/FileUpload';
import { bulkBridgeAPI } from '../services/api';
import './UploadPage.css';

const UploadPage = ({ onUploadSuccess }) => {
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploadError, setUploadError] = useState(null);
  const [uploadSuccess, setUploadSuccess] = useState(false);

  const handleFileSelect = async (file) => {
    setSelectedFile(file);
    setIsUploading(true);
    setUploadProgress(0);
    setUploadError(null);
    setUploadSuccess(false);

    try {
      const response = await bulkBridgeAPI.uploadFile(file, (progress) => {
        setUploadProgress(progress);
      });
      
      if (response.data.success) {
        setUploadSuccess(true);
        setUploadProgress(100);
        
        // Notify parent component
        if (onUploadSuccess) {
          onUploadSuccess(response.data.job);
        }
      } else {
        setUploadError(response.data.message || 'Upload failed');
      }
    } catch (error) {
      console.error('Upload error:', error);
      setUploadError(
        error.response?.data?.message || 
        error.message || 
        'Failed to upload file. Please try again.'
      );
    } finally {
      setIsUploading(false);
      // Reset progress after a short delay
      setTimeout(() => {
        setUploadProgress(0);
        setSelectedFile(null);
      }, 2000);
    }
  };

  const handleNewUpload = () => {
    setUploadError(null);
    setUploadSuccess(false);
    setSelectedFile(null);
    setUploadProgress(0);
  };

  return (
    <div className="upload-page">
      <div className="page-header">
        <h1>Upload CSV File</h1>
      </div>

      <div className="upload-container">
        <div className="upload-card">
          <FileUpload 
            onFileSelect={handleFileSelect}
            isUploading={isUploading}
            uploadProgress={uploadProgress}
            selectedFile={selectedFile}
          />

          {uploadError && (
            <div className="error-message">
              <div className="error-icon">⚠️</div>
              <div className="error-content">
                <h4>Upload Failed</h4>
                <p>{uploadError}</p>
                <button onClick={handleNewUpload} className="retry-button">
                  Try Again
                </button>
              </div>
            </div>
          )}

          {uploadSuccess && (
            <div className="success-message">
              <div className="success-icon">✅</div>
              <div className="success-content">
                <h4>File Uploaded Successfully!</h4>
                <p>Your file has been uploaded and the import process has started.</p>
                <p>Go to the <strong>Monitor</strong> tab to track the progress.</p>
              </div>
            </div>
          )}
        </div>

        <div className="upload-info">
          <h3>CSV Format Requirements</h3>
          <div className="requirements-grid">
            <div className="requirement-item">
              <h4>Required Columns</h4>
              <ul>
                <li>employee_number</li>
                <li>first_name</li>
                <li>last_name</li>
                <li>email</li>
                <li>department</li>
                <li>salary</li>
                <li>currency</li>
                <li>country_code</li>
                <li>start_date</li>
              </ul>
            </div>
            <div className="requirement-item">
              <h4>File Specifications</h4>
              <ul>
                <li>File format: CSV only</li>
                <li>Maximum size: 20MB</li>
                <li>Maximum rows: 50,000</li>
                <li>Encoding: UTF-8</li>
                <li>Date format: YYYY-MM-DD</li>
                <li>Salary: Numeric values only</li>
                <li>Email: Valid email format</li>
              </ul>
            </div>
            <div className="requirement-item">
              <h4>Sample Data</h4>
              <div className="sample-data">
                <code>
                  employee_number,first_name,last_name,email,department,salary,currency,country_code,start_date<br/>
                  EMP001,John,Doe,john.doe@company.com,Engineering,75000,USD,US,2024-01-15
                </code>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UploadPage;
