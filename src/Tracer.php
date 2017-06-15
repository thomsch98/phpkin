<?php

namespace whitemerry\phpkin;

use whitemerry\phpkin\Identifier\Identifier;
use whitemerry\phpkin\Logger\Logger;
use whitemerry\phpkin\Sampler\Sampler;

/**
 * Class Tracer
 *
 * @author Piotr Bugaj <whitemerry@outlook.com>
 * @package whitemerry\phpkin
 */
class Tracer
{
    const FLAG_EMPTY = 0;
    const FLAG_DEBUG = 1;

    const FRONTEND = 'frontend';
    const BACKEND = 'backend';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Endpoint
     */
    protected $endpoint;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Metadata
     */
    protected $metaData;

    /**
     * @var int
     */
    protected $startTimestamp;

    /**
     * @var Span[]
     */
    protected $spans = [];

    /**
     * @var string
     */
    protected $profile = Tracer::FRONTEND;

    /**
     * Tracer constructor.
     *
     * @param $name string Name of trace
     * @param $endpoint Endpoint Current application info
     * @param $logger Logger Trace save handler
     * @param $sampler bool|Sampler Set or calculate 'Sampled' - default true
     * @param $traceId Identifier TraceId - default TraceIdentifier
     * @param $traceSpanId Identifier TraceSpanId - default SpandIdentifier
     * @param $traceParentSpanId Identifier ParentTraceSpanId - default null
     * @param int $traceFlags - default 0
     * @param Metadata $metadata Metadata for TraceSpan
     */
    public function __construct($name, $endpoint, $logger, $sampler = null, $traceId = null, $traceSpanId = null, $traceParentSpanId = null, $traceFlags = 0, $metadata = null)
    {
        TracerInfo::init($sampler, $traceId, $traceSpanId, $traceParentSpanId, $traceFlags);

        $this->setName($name);
        $this->setEndpoint($endpoint);
        $this->setLogger($logger);

        $this->startTimestamp = zipkin_timestamp();
        $this->metaData = $metadata;
    }

    /**
     * Set's application profile
     *
     * @param $profile string Tracer::FRONTEND or Tracer::BACKEND
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * Adds Span to trace
     *
     * @param $span Span
     */
    public function addSpan($span)
    {
        if (!TracerInfo::isSampled()) {
            return;
        }

        $this->spans[] = $span->toArray();
    }

    /**
     * Save trace
     */
    public function trace()
    {
        if (!TracerInfo::isSampled()) {
            return;
        }

        if ($this->profile == Tracer::FRONTEND) {
            TracerInfo::setTraceParentSpanId(null);
        }

        $this->addTraceSpan();
        $this->logger->trace($this->spans);
    }

    /**
     * Adds main span to Spans
     */
    protected function addTraceSpan()
    {
        $span = new Span(
            TracerInfo::getTraceSpanId(),
            $this->name,
            new AnnotationBlock(
                $this->endpoint,
                $this->startTimestamp,
                zipkin_timestamp(),
                AnnotationBlock::SERVER
            ),
            $this->metaData,
            TracerInfo::getTraceId(),
            TracerInfo::getTraceParentSpanId()
        );

        $this->addSpan($span);
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
            throw new \InvalidArgumentException('$name must be a string');
        }

        $this->name = $name;
    }

    /**
     * Valid and set endpoint
     *
     * @param $endpoint Endpoint
     *
     * @throws \InvalidArgumentException
     */
    protected function setEndpoint($endpoint)
    {
        if (!($endpoint instanceof Endpoint)) {
            throw new \InvalidArgumentException('$endpoint must be instance of Endpoint');
        }

        $this->endpoint = $endpoint;
    }

    /**
     * Valid and set logger
     *
     * @param $logger Logger
     *
     * @throws \InvalidArgumentException
     */
    protected function setLogger($logger)
    {
        if (!($logger instanceof Logger)) {
            throw new \InvalidArgumentException('$logger must be instance of Logger');
        }

        $this->logger = $logger;
    }
}
