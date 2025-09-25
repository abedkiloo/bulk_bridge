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
    formData.append('csv', file);
    
    return api.post('/v1/imports', formData, {
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

  // Get import job status
  getJobStatus: async (jobId) => {
    return api.get(`/v1/imports/${jobId}/status`);
  },

  // Get all import jobs
  getJobs: async (limit = 50, offset = 0) => {
    return api.get('/v1/imports', {
      params: { limit, offset }
    });
  },

  // Get import job details
  getJobDetails: async (jobId) => {
    return api.get(`/v1/imports/${jobId}/details`);
  },

  // Get import job basic info
  getJob: async (jobId) => {
    return api.get(`/v1/imports/${jobId}`);
  },

  // Get imported employees for a job
  getImportEmployees: async (jobId, limit = 100, offset = 0) => {
    return api.get(`/v1/imports/${jobId}/employees`, {
      params: { limit, offset }
    });
  },

  // Get import errors for a job
  getImportErrors: async (jobId, limit = 100, offset = 0) => {
    return api.get(`/v1/imports/${jobId}/errors`, {
      params: { limit, offset }
    });
  },

  // Cancel an import job
  cancelJob: async (jobId) => {
    return api.post(`/v1/imports/${jobId}/cancel`);
  },

  // Retry a failed job
  retryJob: async (jobId) => {
    return api.post(`/v1/imports/${jobId}/retry`);
  },

  // Retry only failed rows from a job
  retryFailedRows: async (jobId) => {
    return api.post(`/v1/imports/${jobId}/retry-failed`);
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
