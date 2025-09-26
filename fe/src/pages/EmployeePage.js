import React, { useState, useEffect } from 'react';
import { bulkBridgeAPI } from '../services/api';
import './EmployeePage.css';

const EmployeePage = () => {
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalEmployees, setTotalEmployees] = useState(0);
  const [perPage, setPerPage] = useState(25);
  const [paginationMeta, setPaginationMeta] = useState({});
  
  // Filtering and search state
  const [searchTerm, setSearchTerm] = useState('');
  const [departmentFilter, setDepartmentFilter] = useState('');
  const [sortField, setSortField] = useState('created_at');
  const [sortOrder, setSortOrder] = useState('desc');
  
  // Available departments
  const [departments, setDepartments] = useState([]);
  const [statistics, setStatistics] = useState({});

  useEffect(() => {
    fetchEmployees();
    fetchDepartments();
    fetchStatistics();
  }, [currentPage, perPage, searchTerm, departmentFilter, sortField, sortOrder]);

  const fetchEmployees = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page: currentPage,
        per_page: perPage,
        sort: sortField,
        order: sortOrder
      };
      
      if (searchTerm) params.search = searchTerm;
      if (departmentFilter) params.department = departmentFilter;

      const response = await bulkBridgeAPI.getEmployees(params);
      
      setEmployees(response.data.data || []);
      setPaginationMeta(response.data.meta || {});
      setTotalEmployees(response.data.meta?.total || 0);
      setTotalPages(response.data.meta?.last_page || 1);
    } catch (err) {
      setError('Failed to fetch employees');
      console.error('Error fetching employees:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchDepartments = async () => {
    try {
      const response = await bulkBridgeAPI.getDepartments();
      setDepartments(response.data.data || []);
    } catch (err) {
      console.error('Error fetching departments:', err);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await bulkBridgeAPI.getEmployeeStatistics();
      setStatistics(response.data.data || {});
    } catch (err) {
      console.error('Error fetching statistics:', err);
    }
  };

  const handlePageChange = (newPage) => {
    setCurrentPage(newPage);
  };

  const handlePerPageChange = (newPerPage) => {
    setPerPage(newPerPage);
    setCurrentPage(1);
  };

  const handleSort = (field) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortOrder('asc');
    }
    setCurrentPage(1);
  };

  const handleSearch = (value) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleFilterChange = (filterType, value) => {
    switch (filterType) {
      case 'department':
        setDepartmentFilter(value);
        break;
      default:
        break;
    }
    setCurrentPage(1);
  };

  const clearFilters = () => {
    setSearchTerm('');
    setDepartmentFilter('');
    setCurrentPage(1);
  };


  const formatSalary = (salary) => {
    if (!salary) return 'N/A';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(salary);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getSortIcon = (field) => {
    if (sortField !== field) return '↕️';
    return sortOrder === 'asc' ? '↑' : '↓';
  };

  if (loading && employees.length === 0) {
    return (
      <div className="employee-page">
        <div className="page-header">
          <h1>Employee Management</h1>
        </div>
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading employees...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="employee-page">
      <div className="page-header">
        <h1>Employee Management</h1>
        <p>Manage and view all employee data with advanced filtering and search</p>
      </div>

      {/* Statistics Overview */}
      {statistics.overview && (
        <div className="statistics-overview">
          <div className="stat-card">
            <div className="stat-icon">👥</div>
            <div className="stat-content">
              <div className="stat-value">{statistics.overview.total_employees?.toLocaleString() || 0}</div>
              <div className="stat-label">Total Employees</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">✅</div>
            <div className="stat-content">
              <div className="stat-value">{statistics.overview.active_employees?.toLocaleString() || 0}</div>
              <div className="stat-label">Active</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">⏸️</div>
            <div className="stat-content">
              <div className="stat-value">{statistics.overview.inactive_employees?.toLocaleString() || 0}</div>
              <div className="stat-label">Inactive</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">🏢</div>
            <div className="stat-content">
              <div className="stat-value">{statistics.overview.departments_count || 0}</div>
              <div className="stat-label">Departments</div>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">💰</div>
            <div className="stat-content">
              <div className="stat-value">{formatSalary(statistics.overview.average_salary)}</div>
              <div className="stat-label">Avg Salary</div>
            </div>
          </div>
        </div>
      )}

      {/* Controls */}
      <div className="employee-controls">
        <div className="search-section">
          <div className="search-box">
            <input
              type="text"
              placeholder="Search employees by name, email, or employee number..."
              value={searchTerm}
              onChange={(e) => handleSearch(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
        </div>

        <div className="filters-section">
          <div className="filter-group">
            <label htmlFor="departmentFilter">Department:</label>
            <select
              id="departmentFilter"
              value={departmentFilter}
              onChange={(e) => handleFilterChange('department', e.target.value)}
              className="filter-select"
            >
              <option value="">All Departments</option>
              {departments.map((dept) => (
                <option key={dept} value={dept}>{dept}</option>
              ))}
            </select>
          </div>


          <button onClick={clearFilters} className="clear-filters-btn">
            Clear Filters
          </button>
        </div>

        <div className="pagination-controls">
          <div className="per-page-selector">
            <label htmlFor="perPage">Per page:</label>
            <select
              id="perPage"
              value={perPage}
              onChange={(e) => handlePerPageChange(parseInt(e.target.value))}
              className="per-page-select"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
          
          <button onClick={fetchEmployees} className="refresh-btn">
            🔄 Refresh
          </button>
        </div>
      </div>

      {error && (
        <div className="error-message">
          <p>{error}</p>
          <button onClick={fetchEmployees} className="retry-btn">Retry</button>
        </div>
      )}

      <div className="employee-content">
        <div className="employees-section">
          <div className="section-header">
            <h2>Employees</h2>
            <div className="employees-info">
              Showing {employees.length} of {totalEmployees} employees
              {paginationMeta.from && paginationMeta.to && (
                <span className="range-info">
                  ({(paginationMeta.from || 1)}-{paginationMeta.to || employees.length})
                </span>
              )}
            </div>
          </div>
          
          {employees.length === 0 ? (
            <div className="no-data">
              <div className="no-data-icon">👥</div>
              <h3>No employees found</h3>
              <p>No employees match your current filters</p>
            </div>
          ) : (
            <>
              <div className="employees-table">
                <div className="table-header">
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('employee_number')}
                  >
                    Employee # {getSortIcon('employee_number')}
                  </div>
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('first_name')}
                  >
                    Name {getSortIcon('first_name')}
                  </div>
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('email')}
                  >
                    Email {getSortIcon('email')}
                  </div>
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('department')}
                  >
                    Department {getSortIcon('department')}
                  </div>
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('salary')}
                  >
                    Salary {getSortIcon('salary')}
                  </div>
                  <div 
                    className="header-cell sortable" 
                    onClick={() => handleSort('hire_date')}
                  >
                    Hire Date {getSortIcon('hire_date')}
                  </div>
                  <div className="header-cell">Actions</div>
                </div>
                
                {employees.map((employee) => (
                  <div key={employee.id} className="employee-row">
                    <div className="cell employee-number">
                      <span className="employee-number-text">
                        {employee.employee_number || 'N/A'}
                      </span>
                    </div>
                    
                    <div className="cell employee-name">
                      <div className="name-text">
                        {employee.first_name} {employee.last_name}
                      </div>
                    </div>
                    
                    <div className="cell employee-email">
                      <span className="email-text" title={employee.email}>
                        {employee.email || 'N/A'}
                      </span>
                    </div>
                    
                    <div className="cell employee-department">
                      <span className="department-text">
                        {employee.department || 'N/A'}
                      </span>
                    </div>
                    
                    <div className="cell employee-salary">
                      <span className="salary-text">
                        {formatSalary(employee.salary)}
                      </span>
                    </div>
                    
                    <div className="cell employee-hire-date">
                      <span className="hire-date-text">
                        {formatDate(employee.hire_date)}
                      </span>
                    </div>
                    
                    
                    <div className="cell employee-actions">
                      <div className="action-buttons">
                        <button 
                          className="action-btn view"
                          title="View details"
                        >
                          👁️
                        </button>
                        <button 
                          className="action-btn edit"
                          title="Edit employee"
                        >
                          ✏️
                        </button>
                        <button 
                          className="action-btn delete"
                          title="Delete employee"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              
              {/* Pagination */}
              {totalPages > 1 && (
                <div className="pagination">
                  <div className="pagination-info">
                    Page {currentPage} of {totalPages}
                  </div>
                  
                  <div className="pagination-buttons">
                    <button
                      onClick={() => handlePageChange(1)}
                      disabled={currentPage === 1}
                      className="pagination-btn first"
                      title="First page"
                    >
                      ⏮️
                    </button>
                    
                    <button
                      onClick={() => handlePageChange(currentPage - 1)}
                      disabled={currentPage === 1}
                      className="pagination-btn prev"
                      title="Previous page"
                    >
                      ◀️
                    </button>
                    
                    {/* Page numbers */}
                    {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                      let pageNum;
                      if (totalPages <= 5) {
                        pageNum = i + 1;
                      } else if (currentPage <= 3) {
                        pageNum = i + 1;
                      } else if (currentPage >= totalPages - 2) {
                        pageNum = totalPages - 4 + i;
                      } else {
                        pageNum = currentPage - 2 + i;
                      }
                      
                      return (
                        <button
                          key={pageNum}
                          onClick={() => handlePageChange(pageNum)}
                          className={`pagination-btn page ${currentPage === pageNum ? 'active' : ''}`}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                    
                    <button
                      onClick={() => handlePageChange(currentPage + 1)}
                      disabled={currentPage === totalPages}
                      className="pagination-btn next"
                      title="Next page"
                    >
                      ▶️
                    </button>
                    
                    <button
                      onClick={() => handlePageChange(totalPages)}
                      disabled={currentPage === totalPages}
                      className="pagination-btn last"
                      title="Last page"
                    >
                      ⏭️
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default EmployeePage;
