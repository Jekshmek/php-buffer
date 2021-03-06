<?php
namespace Nelexa\Buffer;

/**
 * Read And Write Binary Data
 *
 * This is class defines methods for reading and writing values of all primitive types. Primitive values are translated to (or from) sequences of bytes according to the buffer's current byte order, which may be retrieved and modified via the order methods. The initial order of a byte buffer is always Buffer::BIG_ENDIAN.
 *
 * @author Ne-Lexa alexey@nelexa.ru
 * @license MIT
 */
abstract class Buffer
{
    const BIG_ENDIAN = "BIG_ENDIAN";
    const LITTLE_ENDIAN = "LITTLE_ENDIAN";

    /**
     * @var int
     */
    private $position = 0;
    /**
     * @var int
     */
    private $limit = 0;
    /**
     * @var string
     */
    private $order = self::BIG_ENDIAN;
    /**
     * @var boolean
     */
    private $isReadOnly = false;

    /**
     * Set buffer position.
     *
     * @param int $position
     * @return Buffer
     * @throws BufferException
     */
    public function setPosition($position)
    {
        $position = (int)$position;
        if ($position > $this->limit) {
            throw new BufferException('Set position ' . $position . ' invalid. Exceeded limit ' . $this->limit);
        }
        $this->position = $position;
        return $this;
    }

    /**
     * Get buffer position
     *
     * @return int
     */
    public final function position()
    {
        return $this->position;
    }

    /**
     * Rewinds this buffer. The position is set to zero.
     *
     * Invoke this method before a sequence of channel-write or get
     * operations, assuming that the limit has already been set
     * appropriately.
     *
     * For example:
     *
     * $buf->writeString("Hello");  // Write remaining data
     * $buf->rewind();              // Rewind buffer
     * $buf->get(5);                // get 5 bytes (Hello)
     *
     * @return Buffer
     */
    public final function rewind()
    {
        $this->setPosition(0);
        return $this;
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
    abstract public function flip();

    /**
     * Returns the number of elements between the current position and the
     * limit.
     *
     * @return int The number of elements remaining in this buffer
     */
    public function remaining()
    {
        return $this->limit - $this->position;
    }

    /**
     * Tells whether there are any elements between the current position and
     * the limit.
     *
     * @return boolean true if, and only if, there is at least one element remaining in this buffer
     */
    public function hasRemaining()
    {
        return $this->position < $this->limit;
    }

    /**
     * Sets this buffer's limit. If the position is larger than the new limit
     * then it is set to the new limit.
     *
     * @param $newLimit int
     * @return Buffer
     * @throws BufferException
     */
    protected function newLimit($newLimit)
    {
        if ($newLimit < 0) {
            throw new BufferException("New Limit < 0");
        }
        $this->limit = $newLimit;
        if ($this->position > $this->limit) {
            $this->position = $this->limit;
        }
        return $this;
    }

    /**
     * Returns this buffer's limit.
     *
     * @return int The limit of this buffer
     */
    public final function size()
    {
        return $this->limit;
    }

    /**
     * Modifies this buffer's byte order.
     *
     * @see Buffer::BIG_ENDIAN
     * @see Buffer::LITTLE_ENDIAN
     *
     * @param string $order The new byte order, either Buffer::BIG_ENDIAN or Buffer::LITTLE_ENDIAN
     * @return Buffer
     */
    public final function setOrder($order)
    {
        $this->order = $order === self::LITTLE_ENDIAN ? $order : self::BIG_ENDIAN;
        return $this;
    }

    /**
     * Retrieves this buffer's byte order.
     *
     * The byte order is used when reading or writing multibyte values, and
     * when creating buffers that are views of this byte buffer. The order of
     * a newly-created byte buffer is always Buffer::BIG_ENDIAN
     *
     * @see Buffer::BIG_ENDIAN
     * @see Buffer::LITTLE_ENDIAN
     *
     * @return string This buffer's byte order
     */
    public final function order()
    {
        return $this->order;
    }

    /**
     * Buffer's byte order is Buffer::LITTLE_ENDIAN
     *
     * @see Buffer::BIG_ENDIAN
     * @see Buffer::LITTLE_ENDIAN
     *
     * @return bool
     */
    protected final function isOrderLE()
    {
        return $this->order === self::LITTLE_ENDIAN;
    }

    /**
     * Set read only buffer.
     *
     * @param boolean $isReadOnly
     * @return Buffer
     */
    public function setReadOnly($isReadOnly)
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }

    /**
     * Is read only buffer.
     *
     * @return boolean
     */
    public final function isReadOnly()
    {
        return $this->isReadOnly;
    }

    /**
     * Skip number bytes.
     *
     * @param int $n The number of bytes to be skipped. The value may be negative.
     * @return Buffer
     */
    public function skip($n)
    {
        $this->setPosition($this->position() + $n);
        return $this;
    }

    /**
     * Skip 1 byte
     *
     * @return Buffer
     */
    public function skipByte()
    {
        $this->skip(1);
        return $this;
    }

    /**
     * Skip short (2 bytes)
     *
     * @return Buffer
     */
    public function skipShort()
    {
        $this->skip(2);
        return $this;
    }

    /**
     * Skip int (4 bytes)
     *
     * @return Buffer
     */
    public function skipInt()
    {
        $this->skip(4);
        return $this;
    }

    /**
     * Skip long (8 bytes)
     *
     * @return Buffer
     */
    public function skipLong()
    {
        $this->skip(8);
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
    abstract protected function get($length);

    /**
     * Reads one input byte and returns true if that byte is nonzero,
     * false if that byte is zero.
     *
     * @return bool the boolean value read.
     * @throws BufferException
     */
    public function getBoolean()
    {
        return (bool)$this->getUnsignedByte();
    }

    /**
     * Reads and returns one input byte.
     * The byte is treated as a signed value in
     * the range -128 through 127, inclusive.
     *
     * @return int the 8-bit value read.
     * @throws BufferException
     */
    public function getByte()
    {
        return Cast::toByte($this->getUnsignedByte());
    }

    /**
     * Reads one input byte, zero-extends
     * it to type int, and returns
     * the result, which is therefore in the range
     * 0 through 255.
     *
     * @return int the unsigned 8-bit value read.
     * @throws BufferException
     */
    public function getUnsignedByte()
    {
        return current(unpack('C', $this->get(1)));
    }

    /**
     * Reads two input bytes and returns
     * a short value in the range -32768 through 32767.
     *
     * @return int the 16-bit value read.
     * @throws BufferException
     */
    public function getShort()
    {
        return Cast::toShort($this->getUnsignedShort());
    }

    /**
     * Reads two input bytes and returns
     * an int value in the range 0 through 65535.
     *
     * @return int the unsigned 16-bit value read.
     * @throws BufferException
     */
    public function getUnsignedShort()
    {
        return current(unpack($this->isOrderLE() ? 'v' : 'n', $this->get(2)));
    }

    /**
     * Reads four input bytes and returns an int value
     * in the range -2147483648 through 2147483647.
     *
     * @return int the int value read.
     * @throws BufferException
     */
    public function getInt()
    {
        return Cast::toInt($this->getUnsignedInt());
    }

    /**
     * Reads four input bytes and returns an long value
     * in the range 0 through 4294967296.
     *
     * @return int the unsigned int value read.
     * @throws BufferException
     */
    public function getUnsignedInt()
    {
        return current(unpack($this->isOrderLE() ? 'V' : 'N', $this->get(4)));
    }

    /**
     * Reads eight input bytes and returns a long value
     * in the range -9223372036854775808 through 9223372036854775807.
     *
     * @return string|int the long value read.
     * @throws BufferException
     */
    public function getLong()
    {
        $data = $this->get(8);
        if (version_compare(PHP_VERSION, '5.6.3') >= 0) {
            return current(unpack($this->isOrderLE() ? 'P' : 'J', $data));
        }
        if ($this->isOrderLE()) {
            $unpack = unpack('Va/Vb', $data);
            return $unpack['a'] + ($unpack['b'] << 32);
        } else {
            $unpack = unpack('Na/Nb', $data);
            return ($unpack['a'] << 32) | $unpack['b'];
        }
    }

    /**
     * Reads $length input bytes and returns a string value.
     *
     * @param $length int
     * @return string
     * @throws BufferException
     */
    public function getString($length)
    {
        if ($length > 0) {
            return $this->get($length);
        }
        return "";
    }

    /**
     * Reads $length bytes from an input stream.
     *
     * @param $length int
     * @return array
     * @throws BufferException
     */
    public function getArrayBytes($length)
    {
        if ($length > 0) {
            return array_values(
                unpack('c*', $this->get($length))
            );
        }
        return array();
    }

    /**
     * Reads in a string that has been encoded using
     * a modified UTF-8 format.
     * 
     * First, two bytes are read and used to
     * construct an unsigned 16-bit integer in
     * exactly the manner of the Buffer::readUnsignedShort()
     * method. This integer value is called the UTF length
     * and specifies the number of additional bytes to be read.
     *
     * Analog java @see java.io.DataOutputStream#readUTF()
     *
     * @return string
     * @throws BufferException
     */
    public function getUTF()
    {
        $size = $this->getUnsignedShort();
        if ($size > 0) {
            $string = $this->getString($size);
            return $string;
        }
        return "";
    }

    /**
     * Reads $length * 2 input bytes and returns a string value.
     *
     * @param $length int
     * @return string
     * @throws BufferException
     */
    public function getUTF16($length)
    {
        if ($length > 0) {
            return implode('', array_map('chr', array_values(unpack('S*', $this->get($length << 1)))));
        }
        return "";
    }

    /**
     * @param bool $bool
     * @return string
     * @throws BufferException
     */
    protected function writeBoolean($bool)
    {
        if ($bool === null) {
            throw new BufferException("null boolean");
        }
        return pack('c', $bool ? 1 : 0);
    }

    /**
     * @param int|string $byte
     * @return string
     * @throws BufferException
     */
    protected function writeByte($byte)
    {
        if ($byte === null) {
            throw new BufferException("null byte");
        }
        return pack('c', $byte);
    }

    /**
     * @param int|string $v
     * @return string
     * @throws BufferException
     */
    protected function writeShort($v)
    {
        if ($v === null) {
            throw new BufferException("null short");
        }
        return pack($this->isOrderLE() ? 'v' : 'n', $v);
    }

    /**
     * @param int|string $v
     * @return string
     * @throws BufferException
     */
    protected function writeInt($v)
    {
        if ($v === null) {
            throw new BufferException("null int");
        }
        return pack($this->isOrderLE() ? 'V' : 'N', $v);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function writeString($string)
    {
        return $string;
    }

    /**
     * @param array $bytes
     * @return string
     */
    protected function writeArrayBytes(array $bytes)
    {
        return call_user_func_array("pack", array_merge(array('c*'), $bytes));
    }

    /**
     * @param int|string $v
     * @return string
     * @throws BufferException
     */
    protected function writeLong($v)
    {
        if ($v === null) {
            throw new BufferException("null long");
        }
        if (version_compare(PHP_VERSION, '5.6.3') >= 0) {
            return pack($this->isOrderLE() ? "P" : "J", $v);
        }

        $left = 0xffffffff00000000;
        $right = 0x00000000ffffffff;
        if ($this->isOrderLE()) {
            $r = ($v & $left) >> 32;
            $l = $v & $right;
            return pack('VV', $l, $r);
        } else {
            $l = ($v & $left) >> 32;
            $r = $v & $right;
            return pack('NN', $l, $r);
        }
    }

    /**
     * Writes a string to the underlying output stream using
     * modified UTF-8 encoding in a machine-independent manner.
     *
     * First, two bytes are written to the output stream as if by the
     * Buffer::writeShort() method giving the number of bytes to
     * follow. This value is the number of bytes actually written out,
     * not the length of the string.
     *
     * Analog java @see java.io.DataOutputStream#writeUTF()
     *
     * @param string $str
     * @return string
     * @throws BufferException
     */
    protected function writeUTF($str)
    {
        if ($str === null) {
            throw new BufferException('$str is null');
        }
        $bytes = unpack('c*', $str);
        $length = sizeof($bytes);
        if ($length > 65535) {
            throw new BufferException('Encoded string too long: ' . $length . ' bytes');
        }
        array_unshift($bytes, 'c*');
        return $this->writeShort($length) . call_user_func_array('pack', $bytes);
    }

    /**
     * @param string $string
     * @return string
     * @throws BufferException
     */
    protected function writeUTF16($string)
    {
        if ($string === null) {
            throw new BufferException('$string is null');
        }
        $args = array_map('ord', str_split($string));
        array_unshift($args, 'S*');
        return call_user_func_array('pack', $args);
    }

    /**
     * Insert Buffer or string.
     *
     * @param Buffer|string $buffer
     * @return Buffer
     * @throws BufferException
     */
    abstract public function insert($buffer);

    /**
     * Insert boolean value
     *
     * @param $bool
     * @return Buffer
     * @throws BufferException
     */
    public function insertBoolean($bool)
    {
        return $this->insert($this->writeBoolean($bool));
    }

    /**
     * Insert byte (-128 >= byte <= 127)
     *
     * @param int|string $byte
     * @return Buffer
     * @throws BufferException
     */
    public function insertByte($byte)
    {
        return $this->insert($this->writeByte($byte));
    }

    /**
     * Insert short value (-32768 >= short <= 32767)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function insertShort($v)
    {
        return $this->insert($this->writeShort($v));
    }

    /**
     * Insert integer value (-2147483648 >= int <= 2147483647)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function insertInt($v)
    {
        return $this->insert($this->writeInt($v));
    }

    /**
     * Insert long value (-9223372036854775808 >= long <= 9223372036854775807)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function insertLong($v)
    {
        return $this->insert($this->writeLong($v));
    }

    /**
     * Insert string
     *
     * @param string $string
     * @return Buffer
     * @throws BufferException
     */
    public function insertString($string)
    {
        return $this->insert($this->writeString($string));
    }

    /**
     * Insert array bytes
     *
     * @param array $bytes
     * @return Buffer
     * @throws BufferException
     */
    public function insertArrayBytes(array $bytes)
    {
        return $this->insert($this->writeArrayBytes($bytes));
    }

    /**
     * Writes a string to the underlying output stream using
     * modified UTF-8 encoding in a machine-independent manner.
     *
     * @see Buffer::writeUTF()
     *
     * @param string $string
     * @return Buffer
     * @throws BufferException
     */
    public function insertUTF($string)
    {
        return $this->insert($this->writeUTF($string));
    }

    /**
     * Insert UTF16 string
     *
     * @param string $string
     * @return Buffer
     * @throws BufferException
     */
    public function insertUTF16($string)
    {
        return $this->insert($this->writeUTF16($string));
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
    abstract public function put($buffer);

    /**
     * Put boolean value
     *
     * @param $bool
     * @return Buffer
     * @throws BufferException
     */
    public function putBoolean($bool)
    {
        return $this->put($this->writeBoolean($bool));
    }

    /**
     * Put byte (-128 >= byte <= 127)
     *
     * @param int|string $byte
     * @return Buffer
     * @throws BufferException
     */
    public function putByte($byte)
    {
        return $this->put($this->writeByte($byte));
    }

    /**
     * Put short value (-32768 >= short <= 32767)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function putShort($v)
    {
        return $this->put($this->writeShort($v));
    }

    /**
     * Put integer value (-2147483648 >= int <= 2147483647)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function putInt($v)
    {
        return $this->put($this->writeInt($v));
    }

    /**
     * Put long value (-9223372036854775808 >= long <= 9223372036854775807)
     *
     * @param int|string $v
     * @return Buffer
     * @throws BufferException
     */
    public function putLong($v)
    {
        return $this->put($this->writeLong($v));
    }

    /**
     * Put string
     *
     * @param string $string
     * @return Buffer
     * @throws BufferException
     */
    public function putString($string)
    {
        return $this->put($this->writeString($string));
    }

    /**
     * Put array bytes
     *
     * @param array $bytes
     * @return Buffer
     * @throws BufferException
     */
    public function putArrayBytes(array $bytes)
    {
        return $this->put($this->writeArrayBytes($bytes));
    }

    /**
     * Put UTF string (Format - java DataOutputStream.writeUTF)
     *
     * @param string $str
     * @return Buffer
     * @throws BufferException
     */
    public function putUTF($str)
    {
        return $this->put($this->writeUTF($str));
    }

    /**
     * Put UTF16 string
     *
     * @param string $str
     * @return Buffer
     * @throws BufferException
     */
    public function putUTF16($str)
    {
        return $this->put($this->writeUTF16($str));
    }

    /**
     * Replace $length bytes in a string or Buffer.
     *
     * @param Buffer|string $buffer
     * @param int $length remove length bytes
     * @return Buffer
     * @throws BufferException
     */
    abstract public function replace($buffer, $length);

    /**
     * Replace by boolean value
     *
     * @param bool $bool
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceBoolean($bool, $length)
    {
        return $this->replace($this->writeBoolean($bool), $length);
    }

    /**
     * Replace by byte (-128 >= byte <= 127)
     *
     * @param int|string $byte
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceByte($byte, $length)
    {
        return $this->replace($this->writeByte($byte), $length);
    }

    /**
     * Replace short value (-32768 >= short <= 32767)
     *
     * @param int|string $v
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceShort($v, $length)
    {
        return $this->replace($this->writeShort($v), $length);
    }

    /**
     * Replace integer value (-2147483648 >= int <= 2147483647)
     *
     * @param int|string $v
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceInt($v, $length)
    {
        return $this->replace($this->writeInt($v), $length);
    }

    /**
     * Replace long value (-9223372036854775808 >= long <= 9223372036854775807)
     *
     * @param int|string $v
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceLong($v, $length)
    {
        return $this->replace($this->writeLong($v), $length);
    }

    /**
     * Replace string
     *
     * @param string $string
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceString($string, $length)
    {
        return $this->replace($this->writeString($string), $length);
    }

    /**
     * Insert array bytes
     *
     * @param array $bytes
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceArrayBytes(array $bytes, $length)
    {
        return $this->replace($this->writeArrayBytes($bytes), $length);
    }

    /**
     * Replace UTF string (Format - java DataOutStream.writeUTF)
     *
     * @param string $str
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceUTF($str, $length)
    {
        return $this->replace($this->writeUTF($str), $length);
    }

    /**
     * Replace UTF16 string
     *
     * @param string $str
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    public function replaceUTF16($str, $length)
    {
        return $this->replace($this->writeUTF16($str), $length);
    }

    /**
     * Remove a certain number of bytes.
     *
     * @param int $length
     * @return Buffer
     * @throws BufferException
     */
    abstract public function remove($length);

    /**
     * Truncate data
     *
     * @return Buffer
     */
    abstract public function truncate();

    /**
     * Close buffer. If this buffer resource that closes the stream.
     */
    abstract public function close();

    /**
     * @return string
     */
    abstract public function toString();

    /**
     * @return string
     */
    function __toString()
    {
        return get_called_class() . '{' .
        'position=' . $this->position() .
        ', limit=' . $this->size() .
        ', order=' . $this->order() .
        ', readOnly=' . ($this->isReadOnly() ? 'true' : 'false') .
        '}';
    }

}