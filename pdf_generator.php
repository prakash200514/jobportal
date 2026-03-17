<?php
require_once 'config.php';

class JobOfferPDFGenerator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function generateJobOfferPDF($applicationId) {
        // Get application details with user and company information
        $stmt = $this->pdo->prepare("
            SELECT 
                ja.id as application_id,
                ja.applied_at,
                ja.cover_letter,
                ja.status,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                j.title as job_title,
                j.description as job_description,
                j.location as job_location,
                j.job_type,
                j.salary_min,
                j.salary_max,
                j.salary_currency,
                j.requirements,
                j.benefits,
                c.name as company_name,
                c.logo as company_logo,
                c.location as company_location,
                c.description as company_description,
                c.website as company_website
            FROM job_applications ja
            JOIN users u ON ja.user_id = u.id
            JOIN jobs j ON ja.job_id = j.id
            JOIN companies c ON j.company_id = c.id
            WHERE ja.id = ?
        ");
        
        $stmt->execute([$applicationId]);
        $data = $stmt->fetch();
        
        if (!$data) {
            throw new Exception("Application not found");
        }
        
        // Create PDF content
        $pdfContent = $this->createPDFContent($data);
        
        // Generate filename (save as HTML so it opens correctly in browser)
        $filename = "Job_Offer_Letter_" . $data['first_name'] . "_" . $data['last_name'] . "_" . date('Y-m-d') . ".html";
        
        return [
            'content' => $pdfContent,
            'filename' => $filename,
            'data' => $data
        ];
    }
    
    private function createPDFContent($data) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    border-bottom: 3px solid #6a38c2;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: #6a38c2;
                }
                .company-info {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                .applicant-info {
                    background-color: #e9ecef;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                .job-details {
                    margin-bottom: 30px;
                }
                .section-title {
                    font-size: 18px;
                    font-weight: bold;
                    color: #6a38c2;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #6a38c2;
                    padding-bottom: 5px;
                }
                .info-row {
                    display: flex;
                    margin-bottom: 10px;
                }
                .info-label {
                    font-weight: bold;
                    width: 150px;
                }
                .info-value {
                    flex: 1;
                }
                .cover-letter {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                .footer {
                    text-align: center;
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                }
                .status-badge {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                .status-pending { background-color: #fff3cd; color: #856404; }
                .status-reviewed { background-color: #d1ecf1; color: #0c5460; }
                .status-shortlisted { background-color: #d4edda; color: #155724; }
                .status-rejected { background-color: #f8d7da; color: #721c24; }
                .status-hired { background-color: #d4edda; color: #155724; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="logo">JobHunt</div>
                <h1>Job Offer & Joining Letter</h1>
                <p>Offer Date: ' . date('F j, Y') . '</p>
            </div>
            
            <div class="company-info">
                <div class="section-title">Company Information</div>
                <div class="info-row">
                    <div class="info-label">Company:</div>
                    <div class="info-value">' . htmlspecialchars($data['company_name']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value">' . htmlspecialchars($data['company_location']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Website:</div>
                    <div class="info-value">' . htmlspecialchars($data['company_website'] ?? 'N/A') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">' . htmlspecialchars($data['company_description'] ?? 'N/A') . '</div>
                </div>
            </div>
            
            <div class="applicant-info">
                <div class="section-title">Candidate Information</div>
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value">' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">' . htmlspecialchars($data['email']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value">' . htmlspecialchars($data['phone'] ?? 'N/A') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Offer Status:</div>
                    <div class="info-value"><span class="status-badge status-hired">Offered</span></div>
                </div>
            </div>
            
            <div class="job-details">
                <div class="section-title">Position Details</div>
                <div class="info-row">
                    <div class="info-label">Position:</div>
                    <div class="info-value">' . htmlspecialchars($data['job_title']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value">' . htmlspecialchars($data['job_location']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Job Type:</div>
                    <div class="info-value">' . htmlspecialchars($data['job_type']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Compensation Range:</div>
                    <div class="info-value">';
        
        if ($data['salary_min'] && $data['salary_max']) {
            $html .= '$' . number_format($data['salary_min']) . ' - $' . number_format($data['salary_max']) . ' ' . $data['salary_currency'];
        } else {
            $html .= 'Salary not specified';
        }
        
        $html .= '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">' . nl2br(htmlspecialchars($data['job_description'])) . '</div>
                </div>
            </div>
            
            <div class="job-details">
                <div class="section-title">Key Responsibilities / Requirements</div>
                <p>' . nl2br(htmlspecialchars($data['requirements'] ?? 'No specific requirements listed')) . '</p>
            </div>
            
            <div class="job-details">
                <div class="section-title">Compensation & Benefits</div>
                <p>' . nl2br(htmlspecialchars($data['benefits'] ?? 'No benefits information available')) . '</p>
            </div>';
        
        if (!empty($data['cover_letter'])) {
            $html .= '
            <div class="cover-letter">
                <div class="section-title">Candidate Cover Letter (Submitted)</div>
                <p>' . nl2br(htmlspecialchars($data['cover_letter'])) . '</p>
            </div>';
        }
        
        $html .= '
            <div class="job-details">
                <div class="section-title">Terms of Employment</div>
                <ul>
                    <li>This offer is contingent upon verification of credentials and documents.</li>
                    <li>Probation period, if applicable, will be per company policy.</li>
                    <li>All company policies and confidentiality clauses apply upon joining.</li>
                </ul>
            </div>

            <div class="job-details">
                <div class="section-title">Acceptance</div>
                <p>Please sign below to indicate your acceptance of this offer and email a copy to HR. A finalized appointment letter will be issued on your joining date.</p>
                <div style="display:flex;justify-content:space-between;gap:40px;margin-top:30px;">
                    <div style="width:48%;border-top:1px solid #ccc;padding-top:6px;text-align:center;font-size:13px;">Candidate Signature</div>
                    <div style="width:48%;border-top:1px solid #ccc;padding-top:6px;text-align:center;font-size:13px;">Authorized Signatory, ' . htmlspecialchars($data['company_name']) . '</div>
                </div>
            </div>

            <div class="footer">
                <p>Generated by JobHunt Platform</p>
                <p>This document contains confidential information and is intended for the applicant and company only.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    public function savePDFToFile($htmlContent, $filename) {
        // Create PDFs directory if it doesn't exist
        $pdfDir = 'pdfs/';
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }
        
        $filepath = $pdfDir . $filename;
        
        // For now, we'll save as HTML file that can be converted to PDF
        // In a production environment, you would use a proper PDF library like TCPDF or mPDF
        file_put_contents($filepath, $htmlContent);
        
        return $filepath;
    }
    
    public function downloadPDF($htmlContent, $filename) {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // For now, we'll output HTML that can be printed as PDF
        // In production, use a proper PDF library
        echo $htmlContent;
        exit;
    }
}
?>
