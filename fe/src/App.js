import React, { useState, useEffect } from 'react';
import Navigation from './components/Navigation';
import UploadPage from './pages/UploadPage';
import MonitorPage from './pages/MonitorPage';
import DetailsPage from './pages/DetailsPage';
import JobDetailsStandalone from './pages/JobDetailsStandalone';
import './App.css';

// Main App Content Component
const AppContent = () => {
  const [activePage, setActivePage] = useState('upload');
  const [currentJob, setCurrentJob] = useState(null);
  const [showJobDetails, setShowJobDetails] = useState(false);

  // Check for jobId parameter on load
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('jobId');
    if (jobId) {
      setShowJobDetails(true);
    }
  }, []);

  const handlePageChange = (page) => {
    setActivePage(page);
  };

  const handleUploadSuccess = (job) => {
    setCurrentJob(job);
    setActivePage('monitor'); // Switch to monitor page after upload
  };

  const handleJobSelect = (job) => {
    setCurrentJob(job);
    setActivePage('monitor'); // Switch to monitor page when job is selected
  };

  const renderPage = () => {
    switch (activePage) {
      case 'upload':
        return <UploadPage onUploadSuccess={handleUploadSuccess} />;
      case 'monitor':
        return (
          <MonitorPage 
            currentJob={currentJob}
            onJobSelect={handleJobSelect}
          />
        );
      case 'details':
        return <DetailsPage />;
      case 'settings':
        return (
          <div className="settings-page">
            <div className="page-header">
              <h1>⚙️ Settings</h1>
              <p>Configure your BulkBridge preferences</p>
            </div>
            <div className="settings-content">
              <div className="sse-info">
                <h3>🔄 Real-Time Streaming</h3>
                <p>BulkBridge uses Server-Sent Events (SSE) for real-time progress updates.</p>
                <ul>
                  <li>✅ Live streaming with automatic reconnection</li>
                  <li>✅ No polling - pure event-driven updates</li>
                  <li>✅ Automatic error recovery</li>
                  <li>✅ Connection status indicators</li>
                </ul>
                <button 
                  onClick={() => setActivePage('history')}
                  className="history-btn"
                >
                  📋 View Upload History
                </button>
              </div>
            </div>
          </div>
        );
      default:
        return <UploadPage onUploadSuccess={handleUploadSuccess} />;
    }
  };

  // If jobId parameter is present, show job details page
  if (showJobDetails) {
    return <JobDetailsStandalone />;
  }

  return (
    <div className="App">
      <Navigation 
        activePage={activePage}
        onPageChange={handlePageChange}
      />

      <main className="App-main">
        {renderPage()}
      </main>

      <footer className="App-footer">
        <div className="container">
          <p>&copy; 2024 BulkBridge - Employee Data Import System</p>
        </div>
      </footer>
    </div>
  );
};

function App() {
  return <AppContent />;
}

export default App;