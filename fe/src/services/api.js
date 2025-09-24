import axios from 'axios';

// Create axios instance with base configuration
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // Laravel backend URL
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// API service for BulkBridge operations
export const bulkBridgeAPI = {
  // Upload CSV file and start import job
  uploadFile: async (file, onUploadProgress) => {
    const formData = new FormData();
    formData.append('file', file);
    
    return api.post('/import/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (onUploadProgress) {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onUploadProgress(percentCompleted);
        }
      },
    });
  },

  // Get import job status via Redis
  getJobStatus: async (jobId) => {
    return api.get(`/import/job/${jobId}/status`);
  },

  // Get all import jobs
  getJobs: async () => {
    return api.get('/import/jobs');
  },

  // Get import job details with rows
  getJobDetails: async (jobId) => {
    return api.get(`/import/job/${jobId}/details`);
  },

  // Get import rows for a job
  getImportRows: async (jobId, page = 1, limit = 50) => {
    return api.get(`/import/job/${jobId}/rows`, {
      params: { page, limit }
    });
  },

  // Get import errors for a job
  getImportErrors: async (jobId) => {
    return api.get(`/import/job/${jobId}/errors`);
  },

  // Cancel an import job
  cancelJob: async (jobId) => {
    return api.post(`/import/job/${jobId}/cancel`);
  },

  // Retry a failed job
  retryJob: async (jobId) => {
    return api.post(`/import/job/${jobId}/retry`);
  },
};

// Error handler for API responses
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default api;
