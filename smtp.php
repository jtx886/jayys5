<?php
class SMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;
    private $socket;
    private $debug = false;

    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->user = SMTP_USER;
        $this->pass = SMTP_PASS;
        $this->from = SMTP_FROM;
        $this->fromName = SMTP_FROM_NAME;
    }

    private function log($msg) {
        if ($this->debug) {
            error_log("[SMTP] " . $msg);
        }
    }

    private function sendCommand($command, $expectCode = null) {
        $this->log(">>> " . $command);
        fputs($this->socket, $command . "\r\n");
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] == ' ') break;
        }
        $this->log("<<< " . trim($response));
        if ($expectCode && strpos($response, $expectCode) !== 0) {
            $this->log("Expected code $expectCode, got response");
        }
        return $response;
    }

    public function send($to, $subject, $body) {
        try {
            $this->socket = @fsockopen('ssl://' . $this->host, $this->port, $errno, $errstr, 30);
            if (!$this->socket) {
                $this->log("Connection failed: $errstr ($errno)");
                return $this->sendViaMail($to, $subject, $body);
            }

            $response = fgets($this->socket, 515);
            $this->log("<<< " . trim($response));

            $this->sendCommand('EHLO ' . $_SERVER['HTTP_HOST'], '250');
            $this->sendCommand('AUTH LOGIN', '334');
            $this->sendCommand(base64_encode($this->user), '334');
            $this->sendCommand(base64_encode($this->pass), '235');

            $from = $this->from;
            $this->sendCommand("MAIL FROM:<$from>", '250');
            $this->sendCommand("RCPT TO:<$to>", '250');
            $this->sendCommand('DATA', '354');

            $boundary = md5(uniqid(time()));
            $headers = "From: " . '=?UTF-8?B?' . base64_encode($this->fromName) . "?=<$from>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: " . '=?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            $message = "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode(strip_tags($body))) . "\r\n";

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body)) . "\r\n";
            $message .= "--$boundary--\r\n";

            $data = $headers . "\r\n" . $message . "\r\n.";
            fputs($this->socket, $data . "\r\n");
            $response = fgets($this->socket, 515);
            $this->log("<<< " . trim($response));

            $this->sendCommand('QUIT', '221');
            fclose($this->socket);
            return true;
        } catch (Exception $e) {
            $this->log("SMTP Error: " . $e->getMessage());
            if ($this->socket) {
                @fclose($this->socket);
            }
            return $this->sendViaMail($to, $subject, $body);
        }
    }

    private function sendViaMail($to, $subject, $body) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->from . ">\r\n";
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $subject, $body, $headers);
    }
}
?>
