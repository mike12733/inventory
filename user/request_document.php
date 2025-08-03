<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] == 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Get document types
$stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
$stmt->execute();
$document_types = $stmt->fetchAll();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_type_id = $_POST['document_type_id'];
    $purpose = trim($_POST['purpose']);
    $preferred_release_date = $_POST['preferred_release_date'];
    
    // Validation
    if (empty($document_type_id) || empty($purpose) || empty($preferred_release_date)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Handle file upload
        $uploaded_file = '';
        if (isset($_FILES['requirements']) && $_FILES['requirements']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['requirements']['type'], $allowed_types)) {
                $error = 'Invalid file type. Please upload JPG, PNG, GIF, or PDF files only.';
            } elseif ($_FILES['requirements']['size'] > $max_size) {
                $error = 'File size too large. Maximum size is 5MB.';
            } else {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['requirements']['name'], PATHINFO_EXTENSION);
                $filename = 'req_' . $user_id . '_' . time() . '.' . $file_extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['requirements']['tmp_name'], $filepath)) {
                    $uploaded_file = $filename;
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        }
        
        if (empty($error)) {
            // Create request
            $stmt = $pdo->prepare("INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date, uploaded_file) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$user_id, $document_type_id, $purpose, $preferred_release_date, $uploaded_file])) {
                $request_id = $pdo->lastInsertId();
                
                // Create notification
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user_id, 
                    'Document Request Submitted', 
                    'Your document request has been submitted successfully. Request ID: ' . $request_id,
                    'portal'
                ]);
                
                $success = 'Document request submitted successfully! You will be notified of any updates.';
            } else {
                $error = 'Failed to submit request. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> Request Document</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="document_type_id" class="form-label">Document Type *</label>
                                <select class="form-control" id="document_type_id" name="document_type_id" required>
                                    <option value="">Select Document Type</option>
                                    <?php foreach ($document_types as $doc_type): ?>
                                        <option value="<?php echo $doc_type['id']; ?>" data-fee="<?php echo $doc_type['fee']; ?>">
                                            <?php echo htmlspecialchars($doc_type['name']); ?> 
                                            (₱<?php echo number_format($doc_type['fee'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Processing time: 2-5 business days</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose of Request *</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required 
                                    placeholder="Please specify the purpose of your request (e.g., For employment, For scholarship, etc.)"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="preferred_release_date" class="form-label">Preferred Release Date *</label>
                                <input type="date" class="form-control" id="preferred_release_date" name="preferred_release_date" required>
                                <small class="text-muted">Please allow at least 3 business days from today</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Upload Requirements (Optional)</label>
                                <input type="file" class="form-control" id="requirements" name="requirements" 
                                    accept=".jpg,.jpeg,.png,.gif,.pdf">
                                <small class="text-muted">Upload valid ID or other requirements. Max size: 5MB. Allowed formats: JPG, PNG, GIF, PDF</small>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Important Information</h6>
                                <ul class="mb-0">
                                    <li>Processing time is 2-5 business days</li>
                                    <li>Payment should be made upon pickup</li>
                                    <li>Please bring a valid ID when claiming your document</li>
                                    <li>You will be notified via email/portal when your document is ready</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date for preferred release date
        document.getElementById('preferred_release_date').min = new Date().toISOString().split('T')[0];
        
        // Show fee when document type is selected
        document.getElementById('document_type_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fee = selectedOption.getAttribute('data-fee');
            if (fee && fee > 0) {
                // You can display the fee information here if needed
            }
        });
    </script>
</body>
</html>