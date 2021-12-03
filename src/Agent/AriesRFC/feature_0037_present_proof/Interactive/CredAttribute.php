<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive;


class CredAttribute
{
    /**
     * @var string
     */
    public $uid;
    /**
     * @var array
     */
    private $cred_info;
    /**
     * @var bool
     */
    public $revealed;
    /**
     * @var string
     */
    public $attr_name;
    /**
     * @var callable|null
     */
    private $on_select;
    /**
     * @var bool
     */
    private $selected;

    /**
     * CredAttribute constructor.
     * @param string $uid
     * @param array $cred_info
     * @param bool $revealed
     * @param string $attr_name
     * @param callable|null $on_select
     */
    public function __construct(
        string $uid, array $cred_info, bool $revealed, string $attr_name, callable $on_select = null
    )
    {
        $this->uid = $uid;
        $this->cred_info = $cred_info;
        $this->revealed = $revealed;
        $this->attr_name = $attr_name;
        $this->on_select = $on_select;
        $this->selected = false;
    }

    /**
     * Get selected property.
     *
     * @return bool
     */
    public function is_selected(): bool
    {
        return $this->selected;
    }

    /**
     * Set selected property.
     *
     * @param bool $value
     * @return void
     */
    public function setSelected(bool $value): void
    {
        if ($this->selected !== $value) {
            $this->selected = $value;
            if ($this->on_select) {
                call_user_func($this->on_select);
            }
        }
    }

    /**
     * Get attr_name property.
     *
     * @return string
     */
    public function getAttrName(): string
    {
        return $this->attr_name;
    }

    /**
     * Get attr_value from the cred_info property.
     *
     * @return array|mixed|null
     */
    public function getAttrValue()
    {
        return
            $this->cred_info['attrs'] ?
                $this->cred_info['attrs'][$this->attr_name] ?? null : [];
    }

    /**
     * Get cred_id from the cred_info property.
     *
     * @return mixed
     */
    public function getCredId()
    {
        return $this->cred_info['cred_id'];
    }

    /**
     * Get cred_info property.
     *
     * @return array
     */
    public function getCredInfo(): array
    {
        return $this->cred_info;
    }
}