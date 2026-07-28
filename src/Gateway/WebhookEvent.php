<?php

namespace Arnipay\Gateway;

use ArrayAccess;

class WebhookEvent implements ArrayAccess
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getType(): ?string
    {
        return $this->data['event'] ?? null;
    }

    /**
     * True when event is payment.completed
     */
    public function isPaid(): bool
    {
        return $this->getType() === 'payment.completed';
    }

    /**
     * Look up a field from data first, then top-level payload.
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        if ($name === 'type') {
            return $this->getType();
        }

        if (isset($this->data['data']) && is_array($this->data['data']) && array_key_exists($name, $this->data['data'])) {
            return $this->data['data'][$name];
        }

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
            return;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}
