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

    public function is_selected()
    {
        return $this->selected;
    }

    public function setSelected(bool $value)
    {
        if ($this->selected != $value) {
            $this->selected = $value;
            if ($this->on_select) {
                call_user_func($this->on_select);
            }
        }
    }

    public function getAttrName()
    {
        return $this->attr_name;
    }

    public function getAttrValue()
    {
        return
            $this->cred_info['attrs'] ?
                $this->cred_info['attrs'][$this->attr_name] ?? null : [];
    }

    public function getCredId()
    {
        return $this->cred_info['cred_id'];
    }

    public function getCredInfo()
    {
        return $this->cred_info;
    }
}