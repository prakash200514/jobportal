// Global variables
let currentUser = null;
let currentPage = 1;
let isLoading = false;

// DOM elements
const menuBtn = document.getElementById("menu-btn");
const navLinks = document.getElementById("nav-links");
const menuBtnIcon = menuBtn.querySelector("i");

// API Base URL
const API_BASE = 'api/';

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
  initializeApp();
  setupEventListeners();
  checkAuthStatus();
});

// Initialize app
function initializeApp() {
  // Mobile menu functionality
  menuBtn.addEventListener("click", (e) => {
    navLinks.classList.toggle("open");
    const isOpen = navLinks.classList.contains("open");
    menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line");
  });

  navLinks.addEventListener("click", (e) => {
    navLinks.classList.remove("open");
    menuBtnIcon.setAttribute("class", "ri-menu-line");
  });

  // Scroll reveal animations
  const scrollRevealOption = {
    distance: "50px",
    origin: "bottom",
    duration: 1000,
  };

  ScrollReveal().reveal(".header__container h2", { ...scrollRevealOption });
  ScrollReveal().reveal(".header__container h1", { ...scrollRevealOption, delay: 500 });
  ScrollReveal().reveal(".header__container p", { ...scrollRevealOption, delay: 1000 });
  ScrollReveal().reveal(".header__btns", { ...scrollRevealOption, delay: 1500 });
  ScrollReveal().reveal(".steps__card", { ...scrollRevealOption, interval: 500 });
  ScrollReveal().reveal(".explore__card", { duration: 1000, interval: 500 });
  ScrollReveal().reveal(".job__card", { ...scrollRevealOption, interval: 500 });
  ScrollReveal().reveal(".offer__card", { ...scrollRevealOption, interval: 500 });

  // Initialize Swiper
  const swiper = new Swiper(".swiper", {
    loop: true,
    autoplay: {
      delay: 3000,
    },
  });
}

// Setup event listeners
function setupEventListeners() {
  // Login form
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
  }

  // Register form
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', handleRegister);
  }

  // Application form
  const applicationForm = document.getElementById('applicationForm');
  if (applicationForm) {
    applicationForm.addEventListener('submit', handleApplication);
  }
}

// Check authentication status
async function checkAuthStatus() {
  try {
    const response = await fetch(API_BASE + 'auth.php', {
      method: 'GET',
      credentials: 'include'
    });
    
    if (response.ok) {
      const data = await response.json();
      currentUser = data.user;
      updateNavForLoggedInUser();
    }
  } catch (error) {
    console.log('User not logged in');
  }
}

// Update navigation for logged in user
function updateNavForLoggedInUser() {
  const navLinks = document.getElementById('nav-links');
  if (currentUser) {
    const isEmployer = currentUser.user_type === 'employer';
    navLinks.innerHTML = `
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="jobs-list.php">Jobs</a></li>
      <li><a href="#service">Services</a></li>
      <li><a href="#client">Client</a></li>
      ${isEmployer ? '<li><a href="add-job.php" class="btn">Post Job</a></li>' : ''}
      ${isEmployer ? '<li><a href="my-jobs.php" class="btn">My Jobs</a></li>' : ''}
      
      <li><button class="btn" onclick="logout()">Logout</button></li>
    `;
  }
}

// API Helper functions
async function apiCall(endpoint, method = 'GET', data = null) {
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include'
  };

  if (data && method !== 'GET') {
    options.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(API_BASE + endpoint, options);
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.error || 'API call failed');
    }
    
    return result;
  } catch (error) {
    console.error('API Error:', error);
    showNotification(error.message, 'error');
    throw error;
  }
}

// Modal functions
function showLoginModal() {
  document.getElementById('loginModal').style.display = 'block';
}

function showRegisterModal() {
  document.getElementById('registerModal').style.display = 'block';
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modals = document.querySelectorAll('.modal');
  modals.forEach(modal => {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });
}

// Authentication functions
async function handleLogin(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const data = {
    email: formData.get('email'),
    password: formData.get('password')
  };

  try {
    const result = await apiCall('auth.php?action=login', 'POST', data);
    currentUser = result.user;
    updateNavForLoggedInUser();
    closeModal('loginModal');
    showNotification('Login successful!', 'success');
  } catch (error) {
    console.error('Login error:', error);
  }
}

async function handleRegister(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const data = {
    first_name: formData.get('first_name'),
    last_name: formData.get('last_name'),
    email: formData.get('email'),
    password: formData.get('password'),
    phone: formData.get('phone'),
    user_type: formData.get('user_type')
  };

  try {
    const result = await apiCall('auth.php?action=register', 'POST', data);
    currentUser = result.user;
    updateNavForLoggedInUser();
    closeModal('registerModal');
    showNotification('Registration successful!', 'success');
  } catch (error) {
    console.error('Registration error:', error);
  }
}

async function logout() {
  try {
    await apiCall('auth.php?action=logout', 'POST');
    currentUser = null;
    location.reload();
  } catch (error) {
    console.error('Logout error:', error);
  }
}

// Job functions
function scrollToJobs() {
  document.getElementById('job').scrollIntoView({ behavior: 'smooth' });
}

function filterJobsByCategory(category) {
  const jobCards = document.querySelectorAll('.job__card');
  jobCards.forEach(card => {
    if (card.dataset.category === category) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
  scrollToJobs();
}

async function searchJobs() {
  const searchTerm = document.getElementById('job-search').value;
  const location = document.getElementById('location-search').value;
  const jobType = document.getElementById('job-type-filter').value;
  
  const params = new URLSearchParams();
  if (searchTerm) params.append('search', searchTerm);
  if (location) params.append('location', location);
  if (jobType) params.append('job_type', jobType);
  
  try {
    const result = await apiCall(`jobs.php?${params.toString()}`);
    displayJobs(result.jobs);
  } catch (error) {
    console.error('Search error:', error);
  }
}

function displayJobs(jobs) {
  const jobGrid = document.getElementById('job-grid');
  jobGrid.innerHTML = '';
  
  jobs.forEach(job => {
    const jobCard = createJobCard(job);
    jobGrid.appendChild(jobCard);
  });
}

function createJobCard(job) {
  const card = document.createElement('div');
  card.className = 'job__card';
  card.dataset.category = job.category_name;
  
  const salaryText = job.salary_min && job.salary_max 
    ? `$${parseInt(job.salary_min).toLocaleString()}-${parseInt(job.salary_max).toLocaleString()}/Year`
    : 'Salary Not Specified';
  
  card.innerHTML = `
    <div class="job__card__header">
      <img src="${job.company_logo}" alt="job" />
      <div>
        <h5>${job.company_name}</h5>
        <h6>${job.location}</h6>
      </div>
    </div>
    <h4>${job.title}</h4>
    <p>${job.description.substring(0, 150)}...</p>
    <div class="job__card__footer">
      <span>${job.positions_available} Positions</span>
      <span>${job.job_type}</span>
      <span>${salaryText}</span>
    </div>
    <div class="job__card__actions">
      <button class="btn btn--small" onclick="goToJob(${job.id})">View Details</button>
      <button class="btn btn--outline" onclick="goToJob(${job.id})">Apply Now</button>
    </div>
  `;
  
  return card;
}

async function viewJobDetails(jobId) {
  // For job seekers, go straight to quiz instead of showing details
  if (currentUser && currentUser.user_type === 'job_seeker') {
    startQuiz(jobId);
    return;
  }
  try {
    const result = await apiCall(`jobs.php?id=${jobId}`);
    const job = result.job;
    
    const modal = document.getElementById('jobDetailsModal');
    const content = document.getElementById('jobDetailsContent');
    
    content.innerHTML = `
      <div class="job-details">
        <div class="job-details__header">
          <img src="${job.company_logo}" alt="company" />
          <div>
            <h2>${job.title}</h2>
            <h3>${job.company_name}</h3>
            <p><i class="ri-map-pin-2-fill"></i> ${job.location}</p>
          </div>
        </div>
        <div class="job-details__content">
          <h4>Job Description</h4>
          <p>${job.description}</p>
          
          ${job.requirements ? `
            <h4>Requirements</h4>
            <p>${job.requirements}</p>
          ` : ''}
          
          ${job.benefits ? `
            <h4>Benefits</h4>
            <p>${job.benefits}</p>
          ` : ''}
          
          <div class="job-details__info">
            <div class="info-item">
              <strong>Job Type:</strong> ${job.job_type}
            </div>
            <div class="info-item">
              <strong>Positions Available:</strong> ${job.positions_available}
            </div>
            <div class="info-item">
              <strong>Salary:</strong> ${job.salary_min && job.salary_max 
                ? `$${parseInt(job.salary_min).toLocaleString()} - $${parseInt(job.salary_max).toLocaleString()}`
                : 'Not Specified'}
            </div>
          </div>
        </div>
        <div class="job-details__actions">
          <button class="btn" onclick="applyToJob(${job.id})">Apply Now</button>
        </div>
      </div>
    `;
    
    modal.style.display = 'block';
  } catch (error) {
    console.error('Error loading job details:', error);
  }
}

function applyToJob(jobId) {
  if (!currentUser) {
    showNotification('Please login to apply for jobs', 'warning');
    showLoginModal();
    return;
  }
  
  // If employer, do nothing (employers shouldn't apply). If job seeker, go to quiz
  if (currentUser.user_type === 'job_seeker') {
    window.location.href = `quiz.php?job_id=${jobId}`;
    return;
  }
  
  showNotification('Employers cannot apply to jobs.', 'warning');
}

// Unified handler: navigate appropriately based on user type
function goToJob(jobId) {
  if (!currentUser) {
    showNotification('Please login first', 'warning');
    showLoginModal();
    return;
  }
  if (currentUser.user_type === 'job_seeker') {
    startQuiz(jobId);
  } else {
    // Employers can only view details (no application)
    viewJobDetails(jobId);
  }
}

function startQuiz(jobId) {
  openInlineQuiz(jobId);
}

async function openInlineQuiz(jobId) {
  // Create modal container
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'block';
  modal.innerHTML = `
    <div class="modal-content" style="max-width:900px">
      <span class="close" onclick="this.parentElement.parentElement.remove()">&times;</span>
      <h2><i class="ri-questionnaire-line"></i> Pre-Application Quiz</h2>
      <div id="quizContainer"><p class="section__description">Loading quiz...</p></div>
    </div>`;
  document.body.appendChild(modal);

  try {
    const res = await apiCall(`quizzes.php?job_id=${jobId}`);
    const questions = res.questions || [];
    const container = modal.querySelector('#quizContainer');
    if (!questions.length) {
      container.innerHTML = '<p class="section__description">No quiz available.</p>';
      return;
    }

    container.innerHTML = '';
    const form = document.createElement('form');
    questions.forEach((q, idx) => {
      const wrap = document.createElement('div');
      wrap.className = 'quiz-question';
      wrap.style.cssText = 'margin:1rem 0;padding:1rem;border:1px solid var(--extra-light);border-radius:6px;';
      wrap.innerHTML = `
        <h4 style="margin:0 0 .75rem 0;color:var(--text-dark);">${idx+1}. ${q.question}</h4>
        <label><input type="radio" name="q_${q.id}" value="A"> ${q.option_a}</label><br>
        <label><input type="radio" name="q_${q.id}" value="B"> ${q.option_b}</label><br>
        <label><input type="radio" name="q_${q.id}" value="C"> ${q.option_c}</label><br>
        <label><input type="radio" name="q_${q.id}" value="D"> ${q.option_d}</label>
      `;
      form.appendChild(wrap);
    });
    const actions = document.createElement('div');
    actions.style.textAlign = 'center';
    actions.innerHTML = '<button type="submit" class="btn">Submit Quiz</button>';
    form.appendChild(actions);
    container.appendChild(form);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const answers = {};
      questions.forEach((q) => {
        const el = form.querySelector(`input[name="q_${q.id}"]:checked`);
        if (el) answers[q.id] = el.value;
      });
      try {
        const result = await apiCall('quizzes.php', 'POST', { job_id: jobId, answers });
        if (result.passed) {
          modal.remove();
          window.location.href = `apply.php?job_id=${jobId}`;
        } else {
          showNotification(`Score: ${result.score}%. Minimum 70% required.`, 'warning');
        }
      } catch (err) {}
    });
  } catch (err) {
    showNotification('Failed to load quiz', 'error');
  }
}

async function handleApplication(e) {
  e.preventDefault();
  
  if (!currentUser) {
    showNotification('Please login to apply for jobs', 'warning');
    return;
  }
  
  const formData = new FormData(e.target);
  
  try {
    const response = await fetch(API_BASE + 'applications.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    
    const result = await response.json();
    
    if (response.ok) {
      showNotification('Application submitted successfully!', 'success');
      closeModal('applicationModal');
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    console.error('Application error:', error);
  }
}

async function showMyApplications() {
  if (!currentUser) {
    showNotification('Please login to view applications', 'warning');
    return;
  }
  // Guard: only job seekers can view their applications
  if (currentUser.user_type !== 'job_seeker') {
    return;
  }
  
  try {
    const result = await apiCall('applications.php');
    displayApplications(result.applications);
  } catch (error) {
    console.error('Error loading applications:', error);
  }
}

function displayApplications(applications) {
  // Create a modal to display applications
  const modal = document.createElement('div');
  modal.className = 'modal';
  modal.style.display = 'block';
  
  modal.innerHTML = `
    <div class="modal-content">
      <span class="close" onclick="this.parentElement.parentElement.remove()">&times;</span>
      <div class="applications-list">
        ${applications.map(app => `
          <div class="application-item">
            <h4>${app.job_title}</h4>
            <p><strong>Company:</strong> ${app.company_name}</p>
            <p><strong>Status:</strong> <span class="status-${app.status}">${app.status}</span></p>
            <p><strong>Applied:</strong> ${new Date(app.applied_at).toLocaleDateString()}</p>
          </div>
        `).join('')}
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
}

// Utility functions
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    z-index: 10000;
    max-width: 300px;
  `;
  
  switch (type) {
    case 'success':
      notification.style.backgroundColor = '#4CAF50';
      break;
    case 'error':
      notification.style.backgroundColor = '#f44336';
      break;
    case 'warning':
      notification.style.backgroundColor = '#ff9800';
      break;
    default:
      notification.style.backgroundColor = '#2196F3';
  }
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.remove();
  }, 5000);
}

// Load more jobs function
async function loadMoreJobs() {
  if (isLoading) return;
  
  isLoading = true;
  currentPage++;
  
  try {
    const result = await apiCall(`jobs.php?page=${currentPage}`);
    const newJobs = result.jobs;
    
    if (newJobs.length > 0) {
      const jobGrid = document.getElementById('job-grid');
      newJobs.forEach(job => {
        const jobCard = createJobCard(job);
        jobGrid.appendChild(jobCard);
      });
    } else {
      document.getElementById('job-pagination').style.display = 'none';
    }
  } catch (error) {
    console.error('Error loading more jobs:', error);
  } finally {
    isLoading = false;
  }
}