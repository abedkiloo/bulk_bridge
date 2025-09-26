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
  getJobs: async (limit = 50, offset = 0, filter = null, search = null) => {
    const params = { limit, offset };
    if (filter && filter !== 'all') {
      params.status = filter;
    }
    if (search) {
      params.search = search;
    }
    return api.get('/v1/imports', { params });
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

  // Employee Management APIs
  getEmployees: async (params = {}) => {
    return api.get('/v1/employees', { params });
  },

  getEmployee: async (id) => {
    return api.get(`/v1/employees/${id}`);
  },

  updateEmployee: async (id, data) => {
    return api.put(`/v1/employees/${id}`, data);
  },

  deleteEmployee: async (id) => {
    return api.delete(`/v1/employees/${id}`);
  },

  getDepartments: async () => {
    return api.get('/v1/employees/departments');
  },

  getEmployeeStatistics: async () => {
    return api.get('/v1/employees/statistics');
  },

  clearAllEmployees: async () => {
    return api.delete('/v1/employees/clear-all');
  },

  bulkUpdateEmployees: async (employeeIds, updates) => {
    return api.post('/v1/employees/bulk-update', {
      employee_ids: employeeIds,
      updates: updates
    });
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
