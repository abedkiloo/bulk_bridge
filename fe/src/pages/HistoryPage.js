import React, { useState, useEffect } from 'react';
import { bulkBridgeAPI } from '../services/api';
import './HistoryPage.css';

const HistoryPage = ({ onJobSelect }) => {
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
      setError('Failed to fetch upload history');
      console.error('Error fetching jobs:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleRetry = async (jobId) => {
    try {
      await bulkBridgeAPI.retryJob(jobId);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to retry job');
      console.error('Error retrying job:', err);
    }
  };

  const handleCancel = async (jobId) => {
    try {
      await bulkBridgeAPI.cancelJob(jobId);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to cancel job');
      console.error('Error canceling job:', err);
    }
  };

  const handleJobClick = (job) => {
    if (onJobSelect) {
      onJobSelect(job);
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


  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  const filteredJobs = jobs.filter(job => {
    const matchesFilter = filter === 'all' || job.status === filter;
    const matchesSearch = job.original_filename.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesFilter && matchesSearch;
  });

  const getFilterStats = () => {
    const stats = {
      all: jobs.length,
      completed: jobs.filter(j => j.status === 'completed').length,
      failed: jobs.filter(j => j.status === 'failed').length,
      processing: jobs.filter(j => j.status === 'processing').length,
      pending: jobs.filter(j => j.status === 'pending').length,
    };
    return stats;
  };

  const stats = getFilterStats();

  if (loading) {
    return (
      <div className="history-page">
        <div className="page-header">
          <h1>📋 Upload History</h1>
          <p>View and manage all your previous uploads</p>
        </div>
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading upload history...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="history-page">
      <div className="page-header">
        <h1>📋 Upload History</h1>
        <p>View and manage all your previous uploads</p>
      </div>

      <div className="history-controls">
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

      <div className="history-content">
        {filteredJobs.length === 0 ? (
          <div className="no-data">
            <div className="no-data-icon">📁</div>
            <h3>No uploads found</h3>
            <p>
              {searchTerm 
                ? `No uploads match "${searchTerm}"`
                : filter === 'all' 
                  ? 'No uploads have been made yet'
                  : `No ${filter} uploads found`
              }
            </p>
            {filter === 'all' && !searchTerm && (
              <p>Go to the Upload tab to start importing data</p>
            )}
          </div>
        ) : (
          <div className="jobs-grid">
            {filteredJobs.map((job) => (
              <div key={job.job_id} className="job-card">
                <div className="job-card-header">
                  <div className="job-status">
                    <span 
                      className="status-icon"
                      style={{ color: getStatusColor(job.status) }}
                    >
                      {getStatusIcon(job.status)}
                    </span>
                    <span className="status-text">{job.status}</span>
                  </div>
                  <div className="job-actions">
                    {job.status === 'failed' && (
                      <button 
                        onClick={() => handleRetry(job.job_id)}
                        className="action-btn retry"
                        title="Retry job"
                      >
                        🔄
                      </button>
                    )}
                    {(job.status === 'pending' || job.status === 'processing') && (
                      <button 
                        onClick={() => handleCancel(job.job_id)}
                        className="action-btn cancel"
                        title="Cancel job"
                      >
                        🚫
                      </button>
                    )}
                    <button 
                      onClick={() => handleJobClick(job)}
                      className="action-btn view"
                      title="View details"
                    >
                      👁️
                    </button>
                  </div>
                </div>

                <div className="job-card-content" onClick={() => handleJobClick(job)}>
                  <div className="job-filename">
                    {job.original_filename}
                  </div>
                  
                  <div className="job-stats">
                    <div className="stat-item">
                      <span className="stat-label">Rows:</span>
                      <span className="stat-value">{job.total_rows}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Processed:</span>
                      <span className="stat-value">{job.processed_rows}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Success:</span>
                      <span className="stat-value success">{job.successful_rows}</span>
                    </div>
                    {job.failed_rows > 0 && (
                      <div className="stat-item">
                        <span className="stat-label">Failed:</span>
                        <span className="stat-value error">{job.failed_rows}</span>
                      </div>
                    )}
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
                      <span className="progress-text">
                        {job.total_rows > 0 ? Math.round((job.processed_rows / job.total_rows) * 100) : 0}%
                      </span>
                    </div>
                  )}

                  <div className="job-meta">
                    <div className="job-date">
                      {formatDate(job.created_at)}
                    </div>
                    {job.completed_at && (
                      <div className="job-duration">
                        Duration: {Math.round((new Date(job.completed_at) - new Date(job.created_at)) / 1000)}s
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default HistoryPage;
