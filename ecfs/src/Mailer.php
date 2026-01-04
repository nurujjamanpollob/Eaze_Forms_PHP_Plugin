<?php
namespace EazeWebIT;

class Mailer {
    /**
     * Sends an email using SMTP settings from the database.
     * This is a basic SMTP implementation using sockets to avoid external dependencies.
     */
    public static function send(string $to, string $subject, string $body): bool {
        $host = Settings::get('smtp_host');
        $port = (int)Settings::get('smtp_port', 587);
        $user = Settings::get('smtp_user');
        $pass = Settings::get('smtp_pass');
        $fromEmail = Settings::get('smtp_from_email', 'noreply@example.com');
        $fromName = Settings::get('smtp_from_name', 'EazeWebIT');

        // Remove newlines from variables used in headers to prevent attackers from injecting arbitrary headers
        $subject = str_replace(["\r", "\n"], '', $subject);
        $fromName = str_replace(["\r", "\n"], '', $fromName);
        $to = str_replace(["\r", "\n"], '', $to);

        if (empty($host)) {
            return false;
        }

        $helloHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        try {
            $errno = 0;
            $errstr = '';
            $timeout = 10;

            // Handle SSL (port 465) by prefixing the host with ssl://
            $connectionHost = ($port === 465) ? "ssl://$host" : $host;

            $socket = @fsockopen($connectionHost, $port, $errno, $errstr, $timeout);
            if (!$socket) {
                throw new \Exception("Could not connect to SMTP host $connectionHost on port $port: $errstr ($errno)");
            }

            self::expect($socket, '220');

            self::sendCommand($socket, "EHLO $helloHost", '250');

            // Handle TLS (port 587) using STARTTLS
            if ($port === 587) {
                self::sendCommand($socket, "STARTTLS", '220');
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception("Failed to enable crypto (STARTTLS)");
                }
                // Resend EHLO after STARTTLS
                self::sendCommand($socket, "EHLO $helloHost", '250');
            }

            // Auth
            if (!empty($user) && !empty($pass)) {
                self::sendCommand($socket, "AUTH LOGIN", '334');
                self::sendCommand($socket, base64_encode($user), '334');
                self::sendCommand($socket, base64_encode($pass), '235');
            }

            self::sendCommand($socket, "MAIL FROM: <$fromEmail>", '250');
            self::sendCommand($socket, "RCPT TO: <$to>", '250');
            self::sendCommand($socket, "DATA", '354');

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $fromName <$fromEmail>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "Message-ID: <" . md5(uniqid(time())) . "@" . $helloHost . ">\r\n";

            // Note: The DATA command ends with a single dot on a line.
            // We must also ensure the body doesn't have a single dot on a line by itself (dot stuffing).
            // For simplicity in this basic mailer, we just append the terminator.
            self::sendCommand($socket, $headers . "\r\n" . $body . "\r\n.", '250');

            self::sendCommand($socket, "QUIT", '221');
            @fclose($socket);

            return true;
        } catch (\Exception $e) {
            if (isset($socket) && is_resource($socket)) {
                @fclose($socket);
            }
            Security::logSecurityIncident('mail_error', 'Failed to send email', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Parses a template by replacing placeholders with dynamic data.
     * Placeholders should be in the format {{field_name}}.
     */
    public static function parseTemplate(string $template, array $data): string {
        $parsed = $template;
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            // Ensure value is a string and handle safely
            $valStr = is_array($value) ? json_encode($value) : (string)$value;
            $parsed = str_replace($placeholder, htmlspecialchars($valStr), $parsed);
        }
        
        // Special case for form_data summary
        if (strpos($parsed, '{{form_data}}') !== false) {
            $summary = "<ul>";
            foreach ($data as $key => $value) {
                // Skip system/internal keys in the summary if needed, or include them
                if (in_array($key, ['csrf_token', 'submission_id', 'submitted_by', 'footer_text'])) continue;
                $valStr = is_array($value) ? json_encode($value) : (string)$value;
                $summary .= "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($valStr) . "</li>";
            }
            $summary .= "</ul>";
            $parsed = str_replace('{{form_data}}', $summary, $parsed);
        }

        // Replace any remaining placeholders with empty string or keep them? 
        // Usually, it's better to remove them to avoid showing raw tags.
        return preg_replace('/\{\{.*?\}\}/', '', $parsed);
    }

    private static function sendCommand($socket, $command, $expectedCode) {
        if (@fwrite($socket, $command . "\r\n") === false) {
            throw new \Exception("Failed to write command to socket");
        }
        return self::expect($socket, $expectedCode);
    }

    private static function expect($socket, $expectedCode) {
        $response = "";
        while ($line = @fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        
        if (empty($response)) {
            throw new \Exception("No response from server (connection closed)");
        }

        if (strpos($response, (string)$expectedCode) !== 0) {
            throw new \Exception("Unexpected response: " . trim($response));
        }
        return $response;
    }
}
