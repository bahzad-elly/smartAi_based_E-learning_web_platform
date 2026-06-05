<?php
/**
 * Smart AI E-Learning - Certificate PDF Download
 * Generates a professional PDF certificate using FPDF
 */

include 'components/connect.php';

if (empty($user_id)) {
    header('location: login.php');
    exit;
}

$cert_id = isset($_GET['cert_id']) ? sanitize_input($_GET['cert_id']) : '';
if (!$cert_id) { header('location: quiz.php'); exit; }

// Fetch certificate info
$cert_stmt = $conn->prepare("
    SELECT c.*,
           u.name AS student_name,
           u.email AS student_email,
           q.title AS quiz_title,
           p.title AS course_title
    FROM `certificates` c
    JOIN `users` u ON u.id = c.user_id
    JOIN `quizzes` q ON q.id = c.quiz_id
    LEFT JOIN `playlist` p ON p.id = q.playlist_id
    WHERE c.id = ? AND c.user_id = ?
    LIMIT 1
");
$cert_stmt->execute([$cert_id, $user_id]);
$cert = $cert_stmt->fetch();

if (!$cert) {
    header('location: quiz.php');
    exit;
}

// ── Load FPDF ────────────────────────────────────
require_once __DIR__ . '/lib/fpdf/fpdf.php';

$issued_date = date('F j, Y', strtotime($cert['issued_at']));
$student_name = $cert['student_name'];
$quiz_title   = $cert['quiz_title'];
$course_title = $cert['course_title'] ?? 'General Studies';
$cert_code    = $cert['certificate_code'];

// Verify URL for QR code
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify_certificate.php?code=' . urlencode($cert_code);

// ── QR Code image (download to temp) ─────────────
$qr_api = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($base_url) . '&choe=UTF-8';
$qr_temp = sys_get_temp_dir() . '/qr_' . md5($cert_code) . '.png';
if (!file_exists($qr_temp)) {
    $qr_data = @file_get_contents($qr_api);
    if ($qr_data) file_put_contents($qr_temp, $qr_data);
}

// ── FPDF Certificate Class ────────────────────────
class CertificatePDF extends FPDF {
    public $cert_code = '';
    public $verify_url = '';

    function Header() {}
    function Footer() {}

    function DrawCertificate($student, $quiz, $course, $date, $code, $qr_path) {
        // ── Page setup ─────────────────────────────────
        // A4 Landscape = 297 x 210 mm
        $W = $this->GetPageWidth();
        $H = $this->GetPageHeight();

        // Purple outer border
        $this->SetDrawColor(142, 68, 173);
        $this->SetLineWidth(5);
        $this->Rect(6, 6, $W-12, $H-12);

        // Gold inner border
        $this->SetDrawColor(243, 156, 18);
        $this->SetLineWidth(1.5);
        $this->Rect(12, 12, $W-24, $H-24);

        // ── Background watermark ─────────────────────
        $this->SetFont('Helvetica', 'B', 60);
        $this->SetTextColor(230, 220, 240);
        $this->SetXY(50, 70);
        $this->Cell(0, 0, 'CERTIFIED', 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);

        // ── Decoration dots at corners ───────────────
        $this->SetDrawColor(243, 156, 18);
        $this->SetLineWidth(.8);
        foreach ([[15,15],[15,$H-15],[$W-15,15],[$W-15,$H-15]] as [$cx,$cy]) {
            $this->Circle($cx, $cy, 3);
        }

        // ── LOGO / PLATFORM NAME ─────────────────────
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetTextColor(142, 68, 173);
        $this->SetXY(0, 22);
        $this->Cell($W, 10, 'SMART AI E-LEARNING PLATFORM', 0, 1, 'C');

        // Tagline
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->SetXY(0, 33);
        $this->Cell($W, 6, 'Empowering Learning Through Technology & AI', 0, 1, 'C');

        // Top decorative line
        $this->SetDrawColor(243, 156, 18);
        $this->SetLineWidth(1);
        $y_line = 42;
        $this->Line(25, $y_line, $W-25, $y_line);

        // ── CERTIFICATE TITLE ────────────────────────
        $this->SetFont('Helvetica', 'B', 28);
        $this->SetTextColor(44, 62, 80);
        $this->SetXY(0, 48);
        $this->Cell($W, 14, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');

        // ── This certifies ──────────────────────────
        $this->SetFont('Helvetica', 'I', 12);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(0, 66);
        $this->Cell($W, 8, 'This certifies that', 0, 1, 'C');

        // ── Student Name ─────────────────────────────
        $this->SetFont('Helvetica', 'B', 32);
        $this->SetTextColor(44, 62, 80);
        $this->SetXY(30, 76);
        $this->Cell($W - 60, 18, $student, 'B', 1, 'C');

        // Underline color
        $this->SetDrawColor(142, 68, 173);
        $this->SetLineWidth(1.5);
        $this->Line(60, 95, $W-60, 95);

        // ── Has successfully completed ───────────────
        $this->SetFont('Helvetica', '', 12);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(0, 100);
        $this->Cell($W, 7, 'has successfully completed the assessment in', 0, 1, 'C');

        // ── Quiz/Course Title ─────────────────────────
        $this->SetFont('Helvetica', 'B', 18);
        $this->SetTextColor(142, 68, 173);
        $this->SetXY(30, 110);
        $this->Cell($W - 60, 10, $quiz, 0, 1, 'C');

        // ── Course Name ───────────────────────────────
        $this->SetFont('Helvetica', '', 11);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(0, 122);
        $this->Cell($W, 7, 'Course: ' . $course, 0, 1, 'C');

        // Decorative line
        $this->SetDrawColor(243, 156, 18);
        $this->SetLineWidth(1);
        $this->Line(25, 133, $W-25, 133);

        // ── Footer: Date | QR | Signature ────────────
        $footer_y = 140;

        // Left – Date
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(44, 62, 80);
        $this->SetXY(25, $footer_y);
        $this->Cell(70, 7, $date, 0, 1, 'C');

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->SetXY(25, $footer_y + 8);
        $this->Cell(70, 5, '___________________', 0, 1, 'C');
        $this->SetXY(25, $footer_y + 14);
        $this->Cell(70, 5, 'Date of Completion', 0, 1, 'C');

        // Center – QR Code
        $qr_x = ($W - 28) / 2;
        if ($qr_path && file_exists($qr_path)) {
            $this->Image($qr_path, $qr_x, $footer_y, 28, 28, 'PNG');
        }
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(150, 150, 150);
        $this->SetXY($qr_x - 5, $footer_y + 30);
        $this->Cell(38, 4, 'Scan to Verify', 0, 1, 'C');

        // Right – Signature
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(142, 68, 173);
        $this->SetXY($W - 95, $footer_y);
        $this->Cell(70, 7, 'Smart AI Platform', 0, 1, 'C');

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->SetXY($W - 95, $footer_y + 8);
        $this->Cell(70, 5, '___________________', 0, 1, 'C');
        $this->SetXY($W - 95, $footer_y + 14);
        $this->Cell(70, 5, 'Authorized Signature', 0, 1, 'C');

        // ── Certificate ID at bottom ──────────────────
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(180, 180, 180);
        $this->SetXY(0, $H - 20);
        $this->Cell($W, 5, 'Certificate ID: ' . $code, 0, 1, 'C');
    }

    // Helper circle method
    function Circle($x, $y, $r) {
        $this->Ellipse($x, $y, $r, $r);
    }

    function Ellipse($x, $y, $rx, $ry) {
        $lx = (4/3) * ($GLOBALS['M_SQRT2'] - 1) * $rx;
        $ly = (4/3) * ($GLOBALS['M_SQRT2'] - 1) * $ry;
        $k  = $this->k;
        $h  = $this->h;
        $x  *= $k;
        $y  = ($h - $y) * $k;
        $rx *= $k;
        $ry *= $k;
        $lx *= $k;
        $ly *= $k;
        $this->_out(sprintf(
            '%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c S',
            $x+$rx, $y,
            $x+$rx, $y+$ly, $x+$lx, $y+$ry, $x, $y+$ry,
            $x-$lx, $y+$ry, $x-$rx, $y+$ly, $x-$rx, $y,
            $x-$rx, $y-$ly, $x-$lx, $y-$ry, $x, $y-$ry,
            $x+$lx, $y-$ry, $x+$rx, $y-$ly, $x+$rx, $y
        ));
    }
}

// ── Generate PDF ──────────────────────────────────
$pdf = new CertificatePDF('L', 'mm', 'A4'); // Landscape
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);
$pdf->DrawCertificate(
    $student_name,
    $quiz_title,
    $course_title,
    $issued_date,
    $cert_code,
    $qr_temp
);

// Output as download
$filename = 'Certificate_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $student_name) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit;
