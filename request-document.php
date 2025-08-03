<?php
$page_title = "Request Document";
require_once 'config/session.php';
require_once 'includes/functions.php';

requireLogin();

// Only students and alumni can request documents
if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type_id = sanitizeInput($_POST['document_type_id'] ?? '');
    $purpose = sanitizeInput($_POST['purpose'] ?? '');
    $preferred_release_date = sanitizeInput($_POST['preferred_release_date'] ?? '');
    
    // Validation
    if (empty($document_type_id)) {
        $error = 'Please select a document type.';
    } elseif (empty($purpose)) {
        $error = 'Please specify the purpose of your request.';
    } elseif (empty($preferred_release_date)) {
        $error = 'Please select your preferred release date.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Get document type details
            $sql = "SELECT * FROM document_types WHERE id = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$document_type_id]);
            $document_type = $stmt->fetch();
            
            if (!$document_type) {
                $error = 'Invalid document type selected.';
            } else {
                // Handle file uploads
                $uploaded_files = [];
                
                if (isset($_FILES['requirements']) && !empty($_FILES['requirements']['name'][0])) {
                    foreach ($_FILES['requirements']['name'] as $key => $filename) {
                        if (!empty($filename)) {
                            $file_data = [
                                'name' => $_FILES['requirements']['name'][$key],
                                'type' => $_FILES['requirements']['type'][$key],
                                'tmp_name' => $_FILES['requirements']['tmp_name'][$key],
                                'error' => $_FILES['requirements']['error'][$key],
                                'size' => $_FILES['requirements']['size'][$key]
                            ];
                            
                            $upload_result = uploadFile($file_data);
                            if ($upload_result['success']) {
                                $uploaded_files[] = [
                                    'original_name' => $filename,
                                    'filename' => $upload_result['filename'],
                                    'filepath' => $upload_result['filepath']
                                ];
                            } else {
                                $error = 'File upload failed: ' . $upload_result['message'];
                                break;
                            }
                        }
                    }
                }
                
                if (empty($error)) {
                    // Insert document request
                    $sql = "INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date, uploaded_files, total_fee) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    $uploaded_files_json = json_encode($uploaded_files);
                    
                    if ($stmt->execute([$user_id, $document_type_id, $purpose, $preferred_release_date, $uploaded_files_json, $document_type['fee']])) {
                        $request_id = $conn->lastInsertId();
                        
                        // Send notification to user
                        sendNotification($user_id, 'Document Request Submitted', 
                            "Your request for {$document_type['document_name']} has been submitted successfully. Request ID: $request_id", 
                            'success', $request_id);
                        
                        // Send notification to admin
                        $admin_sql = "SELECT id FROM users WHERE user_type = 'admin' LIMIT 1";
                        $admin_stmt = $conn->prepare($admin_sql);
                        $admin_stmt->execute();
                        $admin = $admin_stmt->fetch();
                        
                        if ($admin) {
                            sendNotification($admin['id'], 'New Document Request', 
                                "A new request for {$document_type['document_name']} has been submitted. Request ID: $request_id", 
                                'info', $request_id);
                        }
                        
                        setFlashMessage('success', 'Document request submitted successfully! You will receive email notifications about status updates.');
                        header('Location: my-requests.php');
                        exit();
                    } else {
                        $error = 'Failed to submit request. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}

// Get document types
$document_types = getDocumentTypes();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="page-header text-center">
            <h1 class="display-4 mb-2">
                <i class="fas fa-file-plus me-3"></i>Request Document
            </h1>
            <p class="lead mb-0">Submit your document request online</p>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Document Request Form
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="document_type_id" class="form-label fw-bold">
                            <i class="fas fa-file-alt me-2"></i>Document Type *
                        </label>
                        <select class="form-select form-select-lg" id="document_type_id" name="document_type_id" required>
                            <option value="">Select Document Type</option>
                            <?php foreach ($document_types as $doc): ?>
                                <option value="<?php echo $doc['id']; ?>" 
                                        data-fee="<?php echo $doc['fee']; ?>" 
                                        data-processing="<?php echo $doc['processing_days']; ?>"
                                        data-requirements="<?php echo htmlspecialchars($doc['requirements']); ?>">
                                    <?php echo htmlspecialchars($doc['document_name']); ?> - ₱<?php echo number_format($doc['fee'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a document type.</div>
                    </div>
                    
                    <!-- Document Info Display -->
                    <div id="document-info" class="alert alert-info" style="display: none;">
                        <h6><i class="fas fa-info-circle me-2"></i>Document Information</h6>
                        <div id="doc-details"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="purpose" class="form-label fw-bold">
                            <i class="fas fa-question-circle me-2"></i>Purpose of Request *
                        </label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                  placeholder="Please specify why you need this document (e.g., job application, school transfer, etc.)" required></textarea>
                        <div class="invalid-feedback">Please specify the purpose of your request.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="preferred_release_date" class="form-label fw-bold">
                            <i class="fas fa-calendar me-2"></i>Preferred Release Date *
                        </label>
                        <input type="date" class="form-control" id="preferred_release_date" name="preferred_release_date" 
                               min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                        <div class="form-text">Please allow at least 3-7 working days for processing.</div>
                        <div class="invalid-feedback">Please select your preferred release date.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="requirements" class="form-label fw-bold">
                            <i class="fas fa-paperclip me-2"></i>Upload Requirements
                        </label>
                        <input type="file" class="form-control" id="requirements" name="requirements[]" 
                               multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="form-text">
                            Upload valid ID, student ID, and any other required documents. 
                            Accepted formats: JPG, PNG, PDF, DOC, DOCX. Max 5MB per file.
                        </div>
                        <div id="file-preview" class="mt-2"></div>
                    </div>
                    
                    <!-- Requirements Info -->
                    <div id="requirements-info" class="alert alert-warning" style="display: none;">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Required Documents</h6>
                        <div id="requirements-list"></div>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle me-2"></i>Important Notes
                            </h6>
                            <ul class="mb-0">
                                <li>All uploaded documents must be clear and readable</li>
                                <li>Processing time is 3-7 working days depending on document type</li>
                                <li>You will receive email notifications about status updates</li>
                                <li>Payment is required before document release</li>
                                <li>Documents are ready for pickup during office hours (8AM-5PM, Mon-Fri)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Request
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Handle document type selection
document.getElementById('document_type_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const docInfo = document.getElementById('document-info');
    const docDetails = document.getElementById('doc-details');
    const reqInfo = document.getElementById('requirements-info');
    const reqList = document.getElementById('requirements-list');
    
    if (this.value) {
        const fee = selectedOption.dataset.fee;
        const processing = selectedOption.dataset.processing;
        const requirements = selectedOption.dataset.requirements;
        
        docDetails.innerHTML = `
            <p class="mb-1"><strong>Fee:</strong> ₱${parseFloat(fee).toLocaleString()}</p>
            <p class="mb-0"><strong>Processing Time:</strong> ${processing} working days</p>
        `;
        
        reqList.innerHTML = `<p class="mb-0">${requirements}</p>`;
        
        docInfo.style.display = 'block';
        reqInfo.style.display = 'block';
    } else {
        docInfo.style.display = 'none';
        reqInfo.style.display = 'none';
    }
});

// Handle file upload preview
document.getElementById('requirements').addEventListener('change', function() {
    const preview = document.getElementById('file-preview');
    preview.innerHTML = '';
    
    if (this.files.length > 0) {
        const fileList = document.createElement('div');
        fileList.className = 'mt-2';
        
        Array.from(this.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'alert alert-info py-2 px-3 mb-1';
            fileItem.innerHTML = `
                <i class="fas fa-file me-2"></i>
                <strong>${file.name}</strong> 
                <span class="text-muted">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
            `;
            fileList.appendChild(fileItem);
        });
        
        preview.appendChild(fileList);
    }
});

// Set minimum date to today + 3 days
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    today.setDate(today.getDate() + 3);
    const minDate = today.toISOString().split('T')[0];
    document.getElementById('preferred_release_date').setAttribute('min', minDate);
});
</script>

<?php require_once 'includes/footer.php'; ?>