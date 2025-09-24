import { useState, useEffect, useRef, useCallback } from 'react';
import { bulkBridgeAPI } from '../services/api';

const useJobStream = (jobId, options = {}) => {
  const {
    pollInterval = 3000, // Poll every 3 seconds (less aggressive)
  } = options;

  const [jobData, setJobData] = useState(null);
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState(null);
  const [lastUpdate, setLastUpdate] = useState(null);
  const [retryCount, setRetryCount] = useState(0);
  
  const pollIntervalRef = useRef(null);
  const isActiveRef = useRef(true);
  const pollJobStatusRef = useRef(null);
  const lastUpdateTimeRef = useRef(null);
  const startPollingRef = useRef(null);
  const stopPollingRef = useRef(null);

  const pollJobStatus = useCallback(async () => {
    if (!jobId || !isActiveRef.current) return;

    try {
      console.log('Polling job status for:', jobId);
      const response = await bulkBridgeAPI.getJobStatus(jobId);
      
      if (response.data.success) {
        const newJobData = response.data.data;
        
        // Only update state if data has actually changed
        setJobData(prevData => {
          if (!prevData || JSON.stringify(prevData) !== JSON.stringify(newJobData)) {
            lastUpdateTimeRef.current = new Date().toISOString();
            setLastUpdate(lastUpdateTimeRef.current);
            return newJobData;
          }
          return prevData;
        });
        
        // Only update connection status if it changed
        setIsConnected(prevConnected => {
          if (!prevConnected) {
            setError(null);
            setRetryCount(0);
            return true;
          }
          return prevConnected;
        });
        
        // Stop polling if job is completed or failed
        if (newJobData.status === 'completed' || newJobData.status === 'failed') {
          console.log('Job completed/failed, stopping polling');
          setIsConnected(false);
          if (pollIntervalRef.current) {
            clearInterval(pollIntervalRef.current);
            pollIntervalRef.current = null;
          }
        }
      } else {
        setError(response.data.message || 'Failed to fetch job status');
        setIsConnected(false);
      }
    } catch (err) {
      console.error('Error polling job status:', err);
      setError('Network error: ' + err.message);
      setIsConnected(false);
      
      // Increment retry count but don't stop polling
      setRetryCount(prev => prev + 1);
    }
  }, [jobId]);

  // Store the function in ref to avoid dependency issues
  pollJobStatusRef.current = pollJobStatus;

  const startPolling = useCallback(() => {
    if (!jobId || pollIntervalRef.current) {
      console.log('Not starting polling - jobId:', jobId, 'pollIntervalRef.current:', pollIntervalRef.current);
      return;
    }

    console.log('Starting polling for job:', jobId, 'with interval:', pollInterval);
    
    // Initial fetch
    if (pollJobStatusRef.current) {
      pollJobStatusRef.current();
    }
    
    // Set up polling interval
    pollIntervalRef.current = setInterval(() => {
      if (isActiveRef.current && pollJobStatusRef.current) {
        pollJobStatusRef.current();
      }
    }, pollInterval);
    
    setIsConnected(true);
  }, [jobId, pollInterval]);

  const stopPolling = useCallback(() => {
    console.log('Stopping polling, current interval:', pollIntervalRef.current);
    if (pollIntervalRef.current) {
      clearInterval(pollIntervalRef.current);
      pollIntervalRef.current = null;
    }
    setIsConnected(false);
  }, []);

  // Store functions in refs to avoid dependency issues
  startPollingRef.current = startPolling;
  stopPollingRef.current = stopPolling;

  const reconnect = useCallback(() => {
    stopPolling();
    setRetryCount(0);
    setError(null);
    setTimeout(() => {
      if (isActiveRef.current) {
        startPolling();
      }
    }, 1000);
  }, [startPolling, stopPolling]);

  const fetchJobStatus = useCallback(async () => {
    if (!jobId) return;

    try {
      const response = await bulkBridgeAPI.getJobStatus(jobId);
      if (response.data.success) {
        setJobData(response.data.data);
        setLastUpdate(new Date().toISOString());
        setError(null);
      } else {
        setError(response.data.message);
      }
    } catch (err) {
      setError('Network error: ' + err.message);
    }
  }, [jobId]);

  // Start/stop polling when jobId changes
  useEffect(() => {
    if (jobId) {
      startPollingRef.current?.();
    } else {
      stopPollingRef.current?.();
    }

    return () => {
      stopPollingRef.current?.();
    };
  }, [jobId]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      isActiveRef.current = false;
      stopPollingRef.current?.();
    };
  }, []);

  return {
    jobData,
    isConnected,
    error,
    lastUpdate,
    connectionAttempts: retryCount,
    reconnect,
    disconnect: stopPolling,
    fetchJobStatus
  };
};

export default useJobStream;