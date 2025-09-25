import React, { useState, useEffect } from 'react';
import ProgressTicker from './ProgressTicker';
import './ProgressTickerDemo.css';

const ProgressTickerDemo = () => {
  const [demoJob, setDemoJob] = useState({
    id: 'demo-job-123',
    status: 'processing',
    total_rows: 1000,
    processed_rows: 0,
    successful_rows: 0,
    failed_rows: 0,
    duplicate_rows: 0,
    progress_percentage: 0
  });

  const [isConnected, setIsConnected] = useState(true);
  const [lastUpdate, setLastUpdate] = useState(new Date().toISOString());

  useEffect(() => {
    const interval = setInterval(() => {
      if (demoJob.status === 'processing') {
        const newProcessed = Math.min(demoJob.processed_rows + Math.floor(Math.random() * 50) + 10, demoJob.total_rows);
        const newSuccessful = Math.min(demoJob.successful_rows + Math.floor(Math.random() * 30) + 5, newProcessed);
        const newFailed = Math.min(demoJob.failed_rows + Math.floor(Math.random() * 10) + 1, newProcessed - newSuccessful);
        const newDuplicates = Math.min(demoJob.duplicate_rows + Math.floor(Math.random() * 5), newProcessed - newSuccessful - newFailed);
        
        const newProgressPercentage = (newProcessed / demoJob.total_rows) * 100;
        
        setDemoJob(prev => ({
          ...prev,
          processed_rows: newProcessed,
          successful_rows: newSuccessful,
          failed_rows: newFailed,
          duplicate_rows: newDuplicates,
          progress_percentage: newProgressPercentage,
          status: newProcessed >= demoJob.total_rows ? 'completed' : 'processing'
        }));
        
        setLastUpdate(new Date().toISOString());
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [demoJob.status, demoJob.total_rows]);

  const resetDemo = () => {
    setDemoJob({
      id: 'demo-job-123',
      status: 'processing',
      total_rows: 1000,
      processed_rows: 0,
      successful_rows: 0,
      failed_rows: 0,
      duplicate_rows: 0,
      progress_percentage: 0
    });
    setLastUpdate(new Date().toISOString());
  };

  return (
    <div className="progress-ticker-demo">
      <div className="demo-header">
        <h2>Progress Ticker Demo</h2>
        <p>This demonstrates the real-time progress ticker with live updates</p>
        <div className="demo-controls">
          <button onClick={resetDemo} className="reset-btn">
            🔄 Reset Demo
          </button>
          <button 
            onClick={() => setIsConnected(!isConnected)} 
            className={`connection-btn ${isConnected ? 'connected' : 'disconnected'}`}
          >
            {isConnected ? '🟢 Connected' : '🔴 Disconnected'}
          </button>
        </div>
      </div>

      <ProgressTicker
        jobData={demoJob}
        isConnected={isConnected}
        lastUpdate={lastUpdate}
      />

      <div className="demo-info">
        <h3>Features Demonstrated:</h3>
        <ul>
          <li>✅ Real-time progress updates with live ticker feed</li>
          <li>✅ Current statistics display with visual cards</li>
          <li>✅ Connection status indicator</li>
          <li>✅ Live updates feed showing incremental changes</li>
          <li>✅ Responsive design for mobile and desktop</li>
          <li>✅ Smooth animations and visual feedback</li>
        </ul>
      </div>
    </div>
  );
};

export default ProgressTickerDemo;
