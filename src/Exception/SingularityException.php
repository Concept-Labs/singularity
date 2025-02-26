<?php
namespace Concept\Singularity\Exception;

//use Concept\Exception\ConceptException;

class SingularityException 
    extends \Exception 
    implements SingularityExceptionInterface
{
    /**
     * Constructor
     * 
     * @param string     $message  The exception message
     * @param int        $code     The exception code
     * @param \Throwable $previous The previous exception
     */
    public function __construct(
        string $message, 
        int $code = 0, 
        ?\Throwable $previous = null
    )
    {
        $message = sprintf('[%s] %s', $code, $message);
        parent::__construct($message, $code, $previous);
    }
}