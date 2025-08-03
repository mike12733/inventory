<?php
$page_title = "Welcome";
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row min-vh-100 align-items-center">
        <div class="col-lg-6">
            <div class="hero-content p-5">
                <h1 class="display-4 fw-bold text-primary mb-4">
                    <i class="fas fa-graduation-cap me-3"></i>
                    LNHS Documents Request Portal
                </h1>
                <p class="lead mb-4">
                    Request your official documents online without the hassle of visiting the school. 
                    Perfect for students and alumni who need certificates, transcripts, and other official documents.
                </p>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="feature-card card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                                <h5>Fast Processing</h5>
                                <p class="text-muted">Quick document processing with real-time status tracking</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-card card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <h5>Secure & Safe</h5>
                                <p class="text-muted">Your personal information is protected and encrypted</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3 flex-wrap">
                    <a href="login.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a href="register.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="p-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Available Documents</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-certificate text-warning me-3"></i>
                                <div>
                                    <strong>Certificate of Enrollment</strong>
                                    <small class="text-muted d-block">Proof of current enrollment status</small>
                                </div>
                                <span class="badge bg-success ms-auto">₱50</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-award text-info me-3"></i>
                                <div>
                                    <strong>Good Moral Certificate</strong>
                                    <small class="text-muted d-block">Certificate of good moral character</small>
                                </div>
                                <span class="badge bg-success ms-auto">₱75</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-scroll text-primary me-3"></i>
                                <div>
                                    <strong>Transcript of Records</strong>
                                    <small class="text-muted d-block">Official academic transcript</small>
                                </div>
                                <span class="badge bg-success ms-auto">₱100</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-medal text-warning me-3"></i>
                                <div>
                                    <strong>Certificate of Graduation</strong>
                                    <small class="text-muted d-block">Official graduation certificate</small>
                                </div>
                                <span class="badge bg-success ms-auto">₱75</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-diploma text-success me-3"></i>
                                <div>
                                    <strong>Diploma Copy</strong>
                                    <small class="text-muted d-block">Certified true copy of diploma</small>
                                </div>
                                <span class="badge bg-success ms-auto">₱150</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How it Works Section -->
    <div class="row py-5 bg-light">
        <div class="col-12">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">How It Works</h2>
                <p class="lead">Simple steps to get your documents</p>
            </div>
            
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fw-bold fs-4">1</span>
                        </div>
                        <h5>Register/Login</h5>
                        <p class="text-muted">Create your account or login with existing credentials</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fw-bold fs-4">2</span>
                        </div>
                        <h5>Submit Request</h5>
                        <p class="text-muted">Fill out the form and upload required documents</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fw-bold fs-4">3</span>
                        </div>
                        <h5>Track Status</h5>
                        <p class="text-muted">Monitor your request progress in real-time</p>
                    </div>
                </div>
                <div class="col-md-3 text-center mb-4">
                    <div class="step-card">
                        <div class="step-number bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fw-bold fs-4">4</span>
                        </div>
                        <h5>Pickup/Receive</h5>
                        <p class="text-muted">Collect your documents when ready</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Info -->
    <div class="row py-5">
        <div class="col-md-8 mx-auto text-center">
            <h3 class="mb-4">Need Help?</h3>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                            <h6>Email Us</h6>
                            <p class="text-muted mb-0">admin@lnhs.edu.ph</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x text-info mb-3"></i>
                            <h6>Office Hours</h6>
                            <p class="text-muted mb-0">8:00 AM - 5:00 PM<br>Monday - Friday</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-graduation-cap fa-2x text-success mb-3"></i>
                            <h6>For Students & Alumni</h6>
                            <p class="text-muted mb-0">Quick and easy document requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>