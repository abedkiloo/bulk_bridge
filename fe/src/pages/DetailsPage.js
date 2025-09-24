import React, { useState, useEffect } from 'react';
import { bulkBridgeAPI } from '../services/api';
import './DetailsPage.css';

const DetailsPage = () => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    fetchJobs();
  }, []);

  const fetchJobs = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await bulkBridgeAPI.getJobs();
      setJobs(response.data.data || []);
    } catch (err) {
      setError('Failed to fetch jobs');
      console.error('Error fetching jobs:', err);
    } finally {
      setLoading(false);
    }
  };


  const handleJobSelect = async (job) => {
    console.log('handleJobSelect called with:', job);
    // Open job details in a new tab using URL parameters
    const jobDetailsUrl = `${window.location.origin}?jobId=${job.job_id}`;
    window.open(jobDetailsUrl, '_blank');
  };

  const handleDispatchJob = async (jobId) => {
    console.log('handleDispatchJob called with:', jobId);
    try {
      await bulkBridgeAPI.dispatchJob(jobId);
      setError(null);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to dispatch job');
      console.error('Error dispatching job:', err);
    }
  };

  const handleRetryJob = async (jobId) => {
    console.log('handleRetryJob called with:', jobId);
    try {
      await bulkBridgeAPI.retryJob(jobId);
      setError(null);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to retry job');
      console.error('Error retrying job:', err);
    }
  };

  const handleCancelJob = async (jobId) => {
    console.log('handleCancelJob called with:', jobId);
    try {
      await bulkBridgeAPI.cancelJob(jobId);
      setError(null);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to cancel job');
      console.error('Error canceling job:', err);
    }
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

  const getJobStatus = (job) => {
    if (job.status === 'completed') {
      const successfulRows = job.successful_rows || 0;
      const failedRows = job.failed_rows || 0;
      
      if (successfulRows > 0 && failedRows === 0) {
        return { status: 'success', message: 'All rows imported successfully' };
      } else if (successfulRows > 0 && failedRows > 0) {
        return { status: 'partial', message: `${successfulRows} successful, ${failedRows} failed` };
      } else {
        return { status: 'failed', message: 'All rows failed validation' };
      }
    }
    return { status: job.status || 'unknown', message: job.status || 'unknown' };
  };


  const filteredJobs = jobs.filter(job => {
    const matchesFilter = filter === 'all' || job.status === filter;
    const matchesSearch = (job.original_filename || '').toLowerCase().includes(searchTerm.toLowerCase());
    return matchesFilter && matchesSearch;
  });

  const getFilterStats = () => {
    const stats = {
      all: jobs.length,
      completed: jobs.filter(j => (j.status || '') === 'completed').length,
      failed: jobs.filter(j => (j.status || '') === 'failed').length,
      processing: jobs.filter(j => (j.status || '') === 'processing').length,
      pending: jobs.filter(j => (j.status || '') === 'pending').length,
    };
    return stats;
  };

  const stats = getFilterStats();

  if (loading) {
    return (
      <div className="details-page">
        <div className="page-header">
          <h1>Job Details & Management</h1>
        </div>
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading job details...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="details-page">
      <div className="page-header">
        <h1>Job Details & Management</h1>
      </div>

      <div className="details-controls">
        <div className="search-box">
          <input
            type="text"
            placeholder="Search by filename..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
          <span className="search-icon">🔍</span>
        </div>

        <div className="filter-tabs">
          {Object.entries(stats).map(([status, count]) => (
            <button
              key={status}
              className={`filter-tab ${filter === status ? 'active' : ''}`}
              onClick={() => setFilter(status)}
            >
              <span className="filter-label">
                {status === 'all' ? 'All' : status.charAt(0).toUpperCase() + status.slice(1)}
              </span>
              <span className="filter-count">{count}</span>
            </button>
          ))}
        </div>

        <button onClick={fetchJobs} className="refresh-btn">
          🔄 Refresh
        </button>
      </div>

      {error && (
        <div className="error-message">
          <p>{error}</p>
          <button onClick={fetchJobs} className="retry-btn">Retry</button>
        </div>
      )}

      <div className="details-content">
        <div className="jobs-section">
          <h2>All Jobs</h2>
          {filteredJobs.length === 0 ? (
            <div className="no-data">
              <div className="no-data-icon">📁</div>
              <h3>No jobs found</h3>
              <p>No jobs match your current filter</p>
            </div>
          ) : (
            <div className="jobs-list">
              <div className="list-header">
                <div className="header-cell filename">Filename</div>
                <div className="header-cell status">Status</div>
                <div className="header-cell metrics">Metrics</div>
                <div className="header-cell progress">Progress</div>
                <div className="header-cell date">Date</div>
                <div className="header-cell actions">Actions</div>
              </div>
              
              {filteredJobs.map((job) => {
                const jobStatus = getJobStatus(job);
                return (
                  <div key={job.job_id} className="job-row">
                    <div className="row-cell filename">
                      <div className="filename-text" title={job.original_filename}>
                        {job.original_filename}
                      </div>
                      {job.completed_at && (
                        <div className="duration-text">
                          {Math.round((new Date(job.completed_at) - new Date(job.created_at)) / 1000)}s
                        </div>
                      )}
                    </div>
                    
                    <div className="row-cell status">
                      <span 
                        className="status-badge"
                        style={{ backgroundColor: getStatusColor(job.status) }}
                      >
                        {getStatusIcon(job.status)} {job.status}
                      </span>
                      <div className="job-status-detail">
                        {jobStatus.message}
                      </div>
                    </div>
                    
                    <div className="row-cell metrics">
                      <div className="metrics-grid">
                        <div className="metric-item">
                          <span className="metric-label">Total:</span>
                          <span className="metric-value">{(job.total_rows || 0).toLocaleString()}</span>
                        </div>
                        <div className="metric-item">
                          <span className="metric-label">Success:</span>
                          <span className="metric-value success">{(job.successful_rows || 0).toLocaleString()}</span>
                        </div>
                        <div className="metric-item">
                          <span className="metric-label">Failed:</span>
                          <span className="metric-value error">{(job.failed_rows || 0).toLocaleString()}</span>
                        </div>
                        <div className="metric-item">
                          <span className="metric-label">Success Rate:</span>
                          <span className="metric-value rate">
                            {(job.total_rows || 0) > 0 ? Math.round(((job.successful_rows || 0) / (job.total_rows || 1)) * 100) : 0}%
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div className="row-cell progress">
                      {job.status === 'processing' ? (
                        <div className="progress-container">
                          <div className="progress-bar">
                            <div 
                              className="progress-fill"
                              style={{ 
                                width: `${(job.total_rows || 0) > 0 ? ((job.processed_rows || 0) / (job.total_rows || 1)) * 100 : 0}%` 
                              }}
                            />
                          </div>
                          <span className="progress-text">
                            {(job.total_rows || 0) > 0 ? Math.round(((job.processed_rows || 0) / (job.total_rows || 1)) * 100) : 0}%
                          </span>
                        </div>
                      ) : job.status === 'completed' ? (
                        <div className="progress-complete">
                          <span className="complete-icon">✅</span>
                          <span className="complete-text">Complete</span>
                        </div>
                      ) : job.status === 'failed' ? (
                        <div className="progress-failed">
                          <span className="failed-icon">❌</span>
                          <span className="failed-text">Failed</span>
                        </div>
                      ) : (
                        <div className="progress-pending">
                          <span className="pending-icon">⏸️</span>
                          <span className="pending-text">{job.status}</span>
                        </div>
                      )}
                    </div>
                    
                    <div className="row-cell date">
                      <div className="date-text">
                        {job.created_at ? new Date(job.created_at).toLocaleString() : 'N/A'}
                      </div>
                    </div>
                    
                    <div className="row-cell actions">
                      <div className="action-buttons">
                        {job.status === 'pending' && (
                          <button 
                            onClick={() => handleDispatchJob(job.job_id)}
                            className="action-btn dispatch"
                            title="Dispatch job to queue"
                          >
                            Dispatch
                          </button>
                        )}
                        {job.status === 'failed' && (
                          <button 
                            onClick={() => handleRetryJob(job.job_id)}
                            className="action-btn retry"
                            title="Retry job"
                          >
                            Retry
                          </button>
                        )}
                        {(job.status === 'pending' || job.status === 'processing') && (
                          <button 
                            onClick={() => handleCancelJob(job.job_id)}
                            className="action-btn cancel"
                            title="Cancel job"
                          >
                            Cancel
                          </button>
                        )}
                        <button 
                          onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('Details button clicked for job:', job.job_id);
                            handleJobSelect(job);
                          }}
                          className="action-btn view"
                          title="View details"
                        >
                          Details
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

      </div>
      
    </div>
  );
};

export default DetailsPage;
