<?php
namespace whitemerry\phpkin;

use whitemerry\phpkin\Identifier\Identifier;

/**
 * Class Span
 *
 * @author Piotr Bugaj <whitemerry@outlook.com>
 * @package whitemerry\phpkin
 */
class Span
{
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var Identifier
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var AnnotationBlock
     */
    protected $annotationBlock;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var Identifier
     */
    protected $traceId;

    /**
     * @var Identifier
     */
    protected $parentId;

    /**
     * Span constructor.
     *
     * @param $id Identifier Span identifier
     * @param $name string Span name
     * @param $annotationBlock AnnotationBlock Annotations with endpoints
     * @param $metadata Metadata Meta annotations
     * @param $traceId Identifier Trace identifier (default from TraceInfo::getTraceId())
     * @param $parentId Identifier Parent identifier (default from TraceInfo::getTraceParentSpanId())
     */
    function __construct(
        $id,
        $name,
        $annotationBlock,
        $metadata = null,
        $traceId = null,
        $parentId = null
    )
    {
        $this->setIdentifier('id', $id);
        $this->setName($name);
        $this->setAnnotationBlock($annotationBlock);
        $this->setMetadata($metadata);
        $this->setIdentifier('traceId', $traceId, [TracerInfo::class, 'getTraceId']);
        $this->setIdentifier('parentId', $parentId, [TracerInfo::class, 'getTraceParentSpanId']);
        $this->setDebug(TracerInfo::getDebug());
    }

    /**
     * @return mixed
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param mixed $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Converts Span to array
     *
     * @return array
     */
    public function toArray()
    {
        $span = [
            'id' => (string) $this->id,
            'traceId' => (string) $this->traceId,
            'name' => $this->name,
            'debug' => $this->getDebug(),
            'timestamp' => $this->annotationBlock->getStartTimestamp(),
            'duration' => $this->annotationBlock->getDuration(),
            'annotations' => $this->annotationBlock->toArray()
        ];

        if ($this->parentId !== null) {
            $span['parentId'] = (string) $this->parentId;
        }

        if ($this->metadata !== null) {
            $span['binaryAnnotations'] = $this->metadata->toArray();
        }

        return $span;
    }

    /**
     * Valid and set name
     *
     * @param $name string
     *
     * @throws \InvalidArgumentException
     */
    protected function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('The name must be a string');
        }

        $this->name = $name;
    }

    /**
     * Valid and set annotation block
     *
     * @param $annotationBlock AnnotationBlock
     *
     * @throws \InvalidArgumentException
     */
    protected function setAnnotationBlock($annotationBlock)
    {
        if (!($annotationBlock instanceof AnnotationBlock)) {
            throw new \InvalidArgumentException('$annotationBlock must be instance of AnnotationBlock');
        }

        $this->annotationBlock = $annotationBlock;
    }

    /**
     * Valid and set metadata
     *
     * @param $metadata Metadata
     *
     * @throws \InvalidArgumentException
     */
    protected function setMetadata($metadata)
    {
        if ($metadata !== null && !($metadata instanceof Metadata)) {
            throw new \InvalidArgumentException('$metadata must be instance of Metadata');
        }

        $this->metadata = $metadata;
    }

    /**
     * Valid and set identifier
     *
     * @param $field string
     * @param $identifier Identifier
     * @param $default callable Default identifier
     *
     * @throws \InvalidArgumentException
     */
    protected function setIdentifier(
        $field,
        $identifier,
        $default = null
    )
    {
        if ($default && $identifier === null) {
            $identifier = call_user_func($default);
        }

        if ($identifier !== null && !($identifier instanceof Identifier)) {
            throw new \InvalidArgumentException('$identifier must be instance of Identifier');
        }

        $this->{$field} = $identifier;
    }
}
