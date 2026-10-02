<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function buildEmailTemplate($title, $bodyHtml) {
    return "
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f4f7; padding:32px 0;'>
      <tr>
        <td align='center'>
          <table role='presentation' width='480' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:Segoe UI, Arial, sans-serif; box-shadow:0 2px 8px rgba(0,0,0,0.06);'>

            <!-- Header -->
            <tr>
              <td style='background-color:#ffffff; padding:24px 32px; text-align:center;'>
                <span style='color:#800000; font-size:20px; font-weight:700; letter-spacing:0.5px;'>REUNITED</span>
                <div style='color:#800000; font-size:14px; margin-top:2px;'>Lost &amp; Found System — PUP Parañaque</div>
              </td>
            </tr>

            <!-- Title strip -->
            <tr>
              <td style='padding:28px 32px 0 32px;'>
                <h2 style='margin:0; font-size:18px; color:#800000;'>$title</h2>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style='padding:12px 32px 28px 32px; font-size:14px; line-height:1.6; color:#333333;'>
                $bodyHtml
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style='background-color:#f4f4f7; padding:16px 32px; text-align:center; font-size:11px; color:#999999;'>
                This is an automated message from the Reunited system. Please do not reply to this email.
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
    ";
}

function sendReadyForClaimEmail($toEmail, $toName, $itemName) {
    if (empty($toEmail)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME']; 
        $mail->Password   = $_ENV['SMTP_PASSWORD'];       
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('reunited.lostandfound@gmail.com', 'Reunited System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Your item \"$itemName\" is Ready for Claim!";

        $body = "
            <p>Hi " . htmlspecialchars($toName) . ",</p>
            <p>Good news! Your item <strong>" . htmlspecialchars($itemName) . "</strong>
            has been matched and is now marked as:</p>
            <p style='text-align:center; margin:20px 0;'>
                <span style='display:inline-block; background-color:#800000; color:#ffffff; font-weight:600; font-size:13px; padding:8px 18px; border-radius:20px;'>
                    READY FOR CLAIM
                </span>
            </p>
            <p>Please visit the Reunited office to claim your item. Bring a valid ID or proof of ownership.</p>
            <p style='margin-top:24px;'>— Reunited Admin Team</p>
        ";

        $mail->Body = buildEmailTemplate("Your item is ready for claim!", $body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function getStudentContact($conn, $studentId) {
    $stmt = $conn->prepare("SELECT student_email, student_name FROM student_register WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return null;
    return ['email' => $row['student_email'], 'name' => $row['student_name']];
}

function getReporterContact($conn, $reporterId) {
    $stmt = $conn->prepare("SELECT staff_email, staff_name FROM staff_register WHERE staff_employee_id = ?");
    $stmt->bind_param("s", $reporterId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return ['email' => $row['staff_email'], 'name' => $row['staff_name']];

    $stmt = $conn->prepare("SELECT student_email, student_name FROM student_register WHERE student_id = ?");
    $stmt->bind_param("s", $reporterId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return ['email' => $row['student_email'], 'name' => $row['student_name']];

    return null;
}

function sendMatchedToFinderEmail($toEmail, $toName, $itemName) {
    if (empty($toEmail)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('reunited.lostandfound@gmail.com', 'Reunited System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Good news! Your found item \"$itemName\" has been matched";

        $body = "
            <p>Hi " . htmlspecialchars($toName) . ",</p>
            <p>The item you found — <strong>" . htmlspecialchars($itemName) . "</strong> —
            has been matched with its owner and is now:</p>
            <p style='text-align:center; margin:20px 0;'>
                <span style='display:inline-block; background-color:#800000; color:#ffffff; font-weight:600; font-size:13px; padding:8px 18px; border-radius:20px;'>
                    READY FOR CLAIM
                </span>
            </p>
            <p>Thank you for helping reunite it with its owner!</p>
            <p style='margin-top:24px;'>— Reunited Admin Team</p>
        ";

        $mail->Body = buildEmailTemplate("Your found item has been matched!", $body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendStyledEmail($toEmail, $toName, $subject, $title, $bodyHtml) {
    if (empty($toEmail)) {
        error_log("Notification email skipped: recipient email is empty.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(
            'reunited.lostandfound@gmail.com',
            'Reunited System'
        );

        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = buildEmailTemplate($title, $bodyHtml);

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


function notifyReportStatus($conn, $type, $id, $status) {

    if ($type === 'lost') {

        $stmt = $conn->prepare("
            SELECT
                item_name AS name,
                user_id AS owner
            FROM lost_items_reports
            WHERE id = ?
            LIMIT 1
        ");

    } elseif ($type === 'found') {

        $stmt = $conn->prepare("
            SELECT
                itemName_found AS name,
                staff_employee_id AS owner
            FROM found_items_reports
            WHERE id = ?
            LIMIT 1
        ");

    } else {
        return false;
    }

    if (!$stmt) {
        error_log("notifyReportStatus prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $report = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$report) {
        error_log("notifyReportStatus: report not found. ID: " . $id);
        return false;
    }

    /*
     * Get recipient information
     */
    if ($type === 'lost') {

        $contact = getStudentContact(
            $conn,
            $report['owner']
        );

    } else {

        $contact = getReporterContact(
            $conn,
            $report['owner']
        );
    }

    if (!$contact) {
        error_log(
            "notifyReportStatus: contact not found for owner " .
            $report['owner']
        );
        return false;
    }

    $itemName = htmlspecialchars(
        $report['name'],
        ENT_QUOTES,
        'UTF-8'
    );

    $recipientName = htmlspecialchars(
        $contact['name'],
        ENT_QUOTES,
        'UTF-8'
    );


    /*
     * APPROVED
     */
    if ($status === 'approved') {
        $subject = "Your Reunited report has been approved";
        $title = "Your report has been approved!";

        $body = "
            <p>Hi {$recipientName},</p>

            <p>
                Your lost and found report for
                <strong>{$itemName}</strong>
                has been reviewed and approved by the Reunited administrator.
            </p>

            <p style='text-align:center; margin:20px 0;'>
                <span style='
                    display:inline-block;
                    background-color:#800000;
                    color:#ffffff;
                    font-weight:600;
                    font-size:13px;
                    padding:8px 18px;
                    border-radius:20px;
                '>
                    APPROVED
                </span>
            </p>

            <p>
                Your report is now available in the Reunited system.
            </p>

            <p style='margin-top:24px;'>
                — Reunited Admin Team
            </p>
        ";

        return sendStyledEmail(
            $contact['email'],
            $contact['name'],
            $subject,
            $title,
            $body
        );
    }


    /*
     * REJECTED
     */
    if ($status === 'rejected') {
        $subject = "Your Reunited report has been rejected";
        $title = "Your report has been rejected";

        $body = "
            <p>Hi {$recipientName},</p>

            <p>
                Your lost and found report for
                <strong>{$itemName}</strong>
                has been reviewed and was not approved by the Reunited administrator.
            </p>

            <p style='text-align:center; margin:20px 0;'>
                <span style='
                    display:inline-block;
                    background-color:#800000;
                    color:#ffffff;
                    font-weight:600;
                    font-size:13px;
                    padding:8px 18px;
                    border-radius:20px;
                '>
                    REJECTED
                </span>
            </p>

            <p>
                If you believe this was done in error, please contact the
                Reunited administrator for assistance.
            </p>

            <p style='margin-top:24px;'>
                — Reunited Admin Team
            </p>
        ";

        return sendStyledEmail(
            $contact['email'],
            $contact['name'],
            $subject,
            $title,
            $body
        );
    }

    return false;
}