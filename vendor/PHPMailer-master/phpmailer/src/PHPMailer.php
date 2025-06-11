<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $Host = '';
    public $Port = 25;
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $SMTPOptions = [];
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = false;
    public $ErrorInfo = '';

    public function __construct($exceptions = null)
    {
        $this->exceptions = $exceptions;
    }

    public function isSMTP()
    {
        return true;
    }

    public function setFrom($address, $name = '')
    {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress($address, $name = '')
    {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    public function send()
    {
        if (empty($this->Host) || empty($this->Username) || empty($this->Password)) {
            throw new Exception('SMTP configuration is incomplete');
        }

        // Basic SMTP connection test
        $smtp = fsockopen($this->Host, $this->Port, $errno, $errstr, 30);
        if (!$smtp) {
            throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
        }

        // Send email using mail() function as fallback
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: {$this->FromName} <{$this->From}>\r\n";

        $result = mail($this->to[0]['address'], $this->Subject, $this->Body, $headers);
        
        if (!$result) {
            throw new Exception('Failed to send email');
        }

        return true;
    }
} 