import React, { useState } from 'react';
import Navigation from './components/Navigation';
import UploadPage from './pages/UploadPage';
import MonitorPage from './pages/MonitorPage';
import HistoryPage from './pages/HistoryPage';
import './App.css';

function App() {
  const [activePage, setActivePage] = useState('upload');
  const [currentJob, setCurrentJob] = useState(null);

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
      case 'history':
        return <HistoryPage onJobSelect={handleJobSelect} />;
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
}

export default App;