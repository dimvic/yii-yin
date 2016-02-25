<?php

namespace dimvic\YiiYin;

use WoohooLabs\Yin\JsonApi\Exception\JsonApiException;
use WoohooLabs\Yin\JsonApi\Schema\Error;

class ApiError extends JsonApiException
{
    public $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',

        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',

        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',

        // SERVER ERROR
        500 => 'Application Error',//'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @inheritDoc
     */
    protected function getErrors()
    {
        $errors = ApiHelper::$responseErrors;
        usort($errors, function ($a, $b) {
            return $a[0] > $b[0];
        });
        $ret = [];
        foreach ($errors as $error) {
            if ($error instanceof \Exception) {
                $status = $error->code;
                $code = $error->code;
                $title = $error->message;
                $detail = "{$error->file} {$error->line}";
                $meta = ['trace' => $error->getTrace()];
            } elseif (!empty($error[1]) && is_array($error[1])) {
                $status = $error[1]['code'];
                $code = $error[1]['code'];
                $title = $error[1]['message'];
                $detail = "{$error[1]['file']} {$error[1]['line']}";
                $meta = ['trace' => $error[1]['trace']];
            } else {
                $status = $error[0];
                $code = !empty($error[1])
                    ? $error[1]
                    : (isset($this->phrases[$status]) ? $this->phrases[$status] : $status);
                $code = mb_strtoupper(trim(preg_replace('/_+/', '_', preg_replace('/[^a-zA-Z]/', '_', $code)), '_'));
                $title = (!empty($error[2])
                        ? $error[2]
                        : (isset($this->phrases[$status]) ? $this->phrases[$status] : 'Application Error')) . '!';
                $detail = !empty($error[3]) ? $error[3] : $title;
                $meta = !empty($error[4]) ? $error[4] : null;
            }

            $error = Error::create()
                ->setStatus($status)
                ->setCode($code)
                ->setTitle($title)
                ->setDetail($detail);

            if ($meta) {
                $error->setMeta($meta);
            }

            $ret[] = $error;
        }
        return $ret;
    }
}
