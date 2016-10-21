<?php
namespace Nelexa\Buffer;


class ResourceBuffer extends Buffer
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * @param resource $resource
     * @throws BufferException
     */
    function __construct($resource)
    {
        $this->setResource($resource);
    }

    /**
     * @param resource $resource
     * @throws BufferException
     */
    protected function setResource($resource)
    {
        if ($resource === null) {
            throw new BufferException("Resource null");
        }
        if (!is_resource($resource)) {
            throw new BufferException("invalid type \$resource - is not resource");
        }
        if (!stream_is_local($resource)) {
            throw new BufferException("invalid argument \$resource - read only resource is not local");
        }
        $meta = stream_get_meta_data($resource);
        if (!$meta['seekable']) {
            throw new BufferException("\$resource cannot seekable stream.");
        }
        $stats = fstat($resource);
        if (isset($stats['size'])) {
            $this->newLimit($stats['size']);
        }
        $this->resource = $resource;
        $this->setPosition(0);
    }

    /**
     * @return string
     */
    public final function toString()
    {
        $position = $this->position();
        $this->rewind();
        $content = stream_get_contents($this->resource, $this->size());
        $this->setPosition($position);
        return $content;
    }

    /**
     * Flips this buffer. The limit is set to the current position and then
     * the position is set to zero.
     *
     * After a sequence of channel-read or put operations, invoke
     * this method to prepare for a sequence of channel-write or relative
     * get operations.
     *
     * @return Buffer
     */
    public function flip()
    {
        $this->newLimit($this->position());
        ftruncate($this->resource, $this->size());
        $this->setPosition(0);
        return $this;
    }

    /**
     * Relative get method.
     * Reads the string at this buffer's current position, and then increments the position.
     *
     * @param int $length
     * @return string The strings at the buffer's current position
     * @throws BufferException
     */
    protected function get($length)
    {
        if ($length > $this->remaining()) {
            throw new BufferException("get length > remaining");
        }
        $str = fread($this->resource, $length);
        if ($str === false) {
            throw new BufferException("error read resource. position - " . $this->position() . ', limit: ' . $this->size());
        }
        $this->skip($length);
        return $str;
    }

    /**
     * @param int $position
     * @return Buffer
     * @throws BufferException
     */
    public function setPosition($position)
    {
        if (!is_numeric($position)) {
            throw new BufferException("position " . $position . " is not numeric");
        }
        if (fseek($this->resource, $position, SEEK_SET) === 0) {
            return parent::setPosition($position);
        } else {
            throw new BufferException("set position " . $position . " failure");
        }
    }


    /**
     * @param Buffer|string $buffer
     * @return Buffer
     * @throws BufferException
     */
    public function insert($buffer)
    {
        if ($this->isReadOnly()) {
            throw new BufferException("Read Only");
        }
        if ($buffer === null) {
            throw new BufferException("null buffer");
        }
        if ($buffer instanceof Buffer) {
            $buffer = $buffer->toString();
        }
        $lengthBuffer = strlen($buffer);
        if ($this->hasRemaining()) {
            $position = $this->position();
            $buffer .= stream_get_contents($this->resource);
            $this->setPosition($position);
        }
        $length = strlen($buffer);

        $lengthWrite = fwrite($this->resource, $buffer, $length);
        if ($lengthWrite === false || $lengthWrite !== $length) {
            throw new BufferException("Not write all bytes. Length: " . $length . ', write length: ' . $lengthWrite);
        }
        $this->newLimit($this->size() + $lengthBuffer);
        $this->skip($lengthBuffer);
        return $this;
    }

    /**
     * Relative put method (optional operation).
     *
     * Writes the given string into this buffer at the current
     * position, and then increments the position.
     *
     * @param Buffer|string $buffer
     * @return Buffer
     * @throws BufferException
     */
    public function put($buffer)
    {
        if ($this->isReadOnly()) {
            throw new BufferException("Read Only");
        }
        if ($buffer === null) {
            throw new BufferException("null buffer");
        }
        $length = null;
        if ($buffer instanceof Buffer) {
            $length = $buffer->size();
            $buffer = $buffer->toString();
        } else {
            $length = strlen($buffer);
        }
        if ($length > $this->remaining()) {
            throw new BufferException("put length > remaining");
        }
        $lengthWrite = fwrite($this->resource, $buffer, $length);
        if ($lengthWrite === false || $lengthWrite !== $length) {
            throw new BufferException("Not write all bytes. Length: " . $length . ', write length: ' . $lengthWrite);
        }
        $this->skip($length);
        return $this;
    }

    /**
     * @param Buffer|string $buffer
     * @param int $length remove length bytes
     * @return Buffer
     * @throws BufferException
     */
    public function replace($buffer, $length)
    {
        $length = (int)$length;
        if ($this->isReadOnly()) {
            throw new BufferException("Read Only");
        }
        if ($length < 0) {
            throw new BufferException("length < 0");
        }
        if ($length > $this->remaining()) {
            throw new BufferException("replace length > remaining");
        }
        if ($buffer === null) {
            throw new BufferException("null buffer");
        }
        if ($buffer instanceof Buffer) {
            $buffer = $buffer->toString();
        }
        $lengthBuffer = strlen($buffer);

        $position = $this->position();
        $this->setPosition($position + $length);
        $buffer .= stream_get_contents($this->resource);
        $this->setPosition($position);
        ftruncate($this->resource, $position);
        $lengthNewBuffer = strlen($buffer);

        $lengthWrite = fwrite($this->resource, $buffer, $lengthNewBuffer);
        if ($lengthWrite === false || $lengthWrite !== $lengthNewBuffer) {
            throw new BufferException("Not write all bytes. Length: " . $lengthNewBuffer . ', write length: ' . $lengthWrite);
        }
        $this->newLimit($this->size() + $lengthBuffer - $length);
        $this->skip($lengthBuffer);
        return $this;
    }

    /**
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function remove($length)
    {
        if ($this->isReadOnly()) {
            throw new BufferException("Read Only");
        }
        if ($length < 0) {
            throw new BufferException("length < 0");
        }
        if ($length > $this->remaining()) {
            throw new BufferException("remove length > remaining");
        }
        $position = $this->position();
        $this->setPosition($position + $length);
        $buffer = stream_get_contents($this->resource);
        $this->setPosition($position);
        ftruncate($this->resource, $position);
        $lengthNewBuffer = strlen($buffer);

        $lengthWrite = fwrite($this->resource, $buffer, $lengthNewBuffer);
        if ($lengthWrite === false || $lengthWrite !== $lengthNewBuffer) {
            throw new BufferException("Not write all bytes. Length: " . $lengthNewBuffer . ', write length: ' . $lengthWrite);
        }
        $this->newLimit($this->size() - $length);
        $this->setPosition($position);
        return $this;
    }

    /**
     * Truncate file
     *
     * @return Buffer
     * @throws BufferException
     */
    public final function truncate()
    {
        if ($this->isReadOnly()) {
            throw new BufferException("Read Only");
        }
        ftruncate($this->resource, 0);
        $this->rewind();
        $this->newLimit(0);
        return $this;
    }

    /**
     * Destruct object, close file description.
     */
    function __destruct()
    {
        $this->close();
    }

    /**
     * Close buffer. If this buffer resource that closes the stream.
     */
    public function close()
    {
        if ($this->resource !== null && is_resource($this->resource)) {
            fclose($this->resource);
            $this->resource = null;
        }
    }


}