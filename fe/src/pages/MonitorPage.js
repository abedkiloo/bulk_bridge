import React, { useState, useEffect } from 'react';
import JobStatus from '../components/JobStatus';
import { bulkBridgeAPI } from '../services/api';
import './MonitorPage.css';

const MonitorPage = ({ currentJob, onJobSelect }) => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedJobId, setSelectedJobId] = useState(null);

  useEffect(() => {
    if (currentJob) {
      setSelectedJobId(currentJob.id);
    }
  }, [currentJob]);

  useEffect(() => {
    fetchJobs();
    
    // Auto-refresh jobs list every 5 seconds (without loading state)
    const interval = setInterval(() => {
      fetchJobs(false);
    }, 5000);
    
    return () => clearInterval(interval);
  }, []);

  const fetchJobs = async (showLoading = true) => {
    if (showLoading) {
      setLoading(true);
    }
    setError(null);
    try {
      const response = await bulkBridgeAPI.getJobs();
      const allJobs = response.data.data || [];
      // Filter out completed jobs - they don't need monitoring
      const activeJobs = allJobs.filter(job => job.status !== 'completed');
      setJobs(activeJobs);
    } catch (err) {
      setError('Failed to fetch jobs');
      console.error('Error fetching jobs:', err);
    } finally {
      if (showLoading) {
        setLoading(false);
      }
    }
  };

  const handleJobSelect = (job) => {
    setSelectedJobId(job.id);
    if (onJobSelect) {
      onJobSelect(job);
    }
  };

  const handleJobComplete = (job) => {
    // Refresh the jobs list when a job completes (without loading state)
    fetchJobs(false);
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed': return '✅';
      case 'failed': return '❌';
      case 'processing': return '⏳';
      case 'pending': return '⏸️';
      case 'cancelled': return '🚫';
      default: return '❓';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return '#28a745';
      case 'failed': return '#dc3545';
      case 'processing': return '#ffc107';
      case 'pending': return '#6c757d';
      case 'cancelled': return '#6c757d';
      default: return '#6c757d';
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
  };


  if (loading) {
    return (
      <div className="monitor-page">
        <div className="page-header">
          <h1>Monitor Uploads</h1>
        </div>
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading jobs...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="monitor-page">
      <div className="page-header">
        <h1>Monitor Uploads</h1>
        <button onClick={() => fetchJobs(true)} className="refresh-btn">
          🔄 Refresh
        </button>
      </div>

      <div className="monitor-container">
        <div className="jobs-sidebar">
          <div className="sidebar-header">
            <h3>Recent Jobs</h3>
            <span className="job-count">{jobs.length} jobs</span>
          </div>
          
          {error && (
            <div className="error-message">
              <p>{error}</p>
              <button onClick={() => fetchJobs(true)} className="retry-btn">Retry</button>
            </div>
          )}

          <div className="jobs-list">
            {jobs.length === 0 ? (
              <div className="no-jobs">
                <p>No uploads found</p>
                <p>Go to the Upload tab to start importing data</p>
              </div>
            ) : (
              jobs.map((job) => (
                <div
                  key={job.id}
                  className={`job-item ${selectedJobId === job.id ? 'selected' : ''}`}
                  onClick={() => handleJobSelect(job)}
                >
                  <div className="job-header">
                    <div className="job-status">
                      <span 
                        className="status-icon"
                        style={{ color: getStatusColor(job.status) }}
                      >
                        {getStatusIcon(job.status)}
                      </span>
                      <span className="status-text">{job.status}</span>
                    </div>
                    <div className="job-time">
                      {formatDate(job.created_at)}
                    </div>
                  </div>
                  
                  <div className="job-details">
                    <div className="job-filename">
                      {job.original_filename}
                    </div>
                    <div className="job-stats">
                      <span>{job.total_rows} rows</span>
                      {job.status === 'processing' && (
                        <span className="progress-text">
                          {job.total_rows > 0 ? Math.round((job.processed_rows / job.total_rows) * 100) : 0}%
                        </span>
                      )}
                    </div>
                  </div>

                  {job.status === 'processing' && (
                    <div className="job-progress">
                      <div className="progress-bar">
                        <div 
                          className="progress-fill"
                          style={{ 
                            width: `${job.total_rows > 0 ? (job.processed_rows / job.total_rows) * 100 : 0}%` 
                          }}
                        />
                      </div>
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>

        <div className="job-details-panel">
          {selectedJobId ? (
            <JobStatus 
              jobId={selectedJobId}
              onJobComplete={handleJobComplete}
            />
          ) : (
            <div className="no-selection">
              <div className="no-selection-content">
                <h3>Select a Job</h3>
                <p>Choose a job from the sidebar to view detailed progress and status information.</p>
                {jobs.length === 0 && (
                  <div className="upload-cta">
                    <p>No jobs available. Start by uploading a CSV file!</p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MonitorPage;
