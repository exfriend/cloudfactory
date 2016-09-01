<?php
namespace Exfriend\CloudFactory;


trait ValidatesItself
{
    /**
     * @var bool
     */
    public $valid = false;
    /**
     * @var callable
     */
    protected $validator;

    public function __construct()
    {
        parent::__construct();
    }

    public function validateUsing(callable $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    public function valid()
    {
        if (is_callable($this->validator)) {
            $this->valid = call_user_func($this->validator, $this);
        } else {
            $this->valid = strlen($this->response) > 0;
        }
        return $this->valid;
    }


}