import React, { useState, useEffect, forwardRef, useImperativeHandle } from 'react';
import { createPortal } from 'react-dom';
import JobDetailsWindow from './JobDetailsWindow';

const WindowManager = forwardRef((props, ref) => {
  const [windows, setWindows] = useState([]);

  const openJobDetailsWindow = (job) => {
    const windowId = `job-${job.job_id}-${Date.now()}`;
    const newWindow = window.open('', windowId, 'width=1200,height=800,scrollbars=yes,resizable=yes');
    
    if (newWindow) {
      // Set up the new window
      newWindow.document.title = `Job Details - ${job.original_filename}`;
      newWindow.document.body.style.margin = '0';
      newWindow.document.body.style.padding = '0';
      
      // Create a root div for React
      const rootDiv = newWindow.document.createElement('div');
      rootDiv.id = 'react-root';
      newWindow.document.body.appendChild(rootDiv);
      
      // Add the window to our state
      setWindows(prev => [...prev, {
        id: windowId,
        window: newWindow,
        jobId: job.job_id,
        rootDiv
      }]);
      
      // Handle window close
      newWindow.addEventListener('beforeunload', () => {
        setWindows(prev => prev.filter(w => w.id !== windowId));
      });
    }
  };

  const closeWindow = (windowId) => {
    setWindows(prev => {
      const windowToClose = prev.find(w => w.id === windowId);
      if (windowToClose) {
        windowToClose.window.close();
      }
      return prev.filter(w => w.id !== windowId);
    });
  };

  // Expose the openJobDetailsWindow function to parent components
  useImperativeHandle(ref, () => ({
    openJobDetailsWindow
  }));

  // Clean up closed windows
  useEffect(() => {
    const interval = setInterval(() => {
      setWindows(prev => prev.filter(w => !w.window.closed));
    }, 1000);
    
    return () => clearInterval(interval);
  }, []);

  return (
    <>
      {windows.map(({ id, jobId, rootDiv }) => {
        if (!rootDiv) return null;
        
        return createPortal(
          <JobDetailsWindow 
            key={id}
            jobId={jobId} 
            onClose={() => closeWindow(id)}
          />,
          rootDiv
        );
      })}
    </>
  );
});

export default WindowManager;
