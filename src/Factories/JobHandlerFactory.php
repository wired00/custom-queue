<?php

namespace Wired00\CustomQueue\Factories;

use Illuminate\Support\Facades\App;
use Wired00\CustomQueue\Contracts\CustomQueueJobHandler;

class JobHandlerFactory
{
    /**
     * @param string $jobIdentifier
     *
     * @return CustomQueueJobHandler
     */
    public function create(string $jobIdentifier)
    {
        //Get the handler class name
        $classname = config('customqueue.handlers.'.$jobIdentifier, '');

        if (!class_exists($classname) ||
            !in_array('Wired00\CustomQueue\Contracts\CustomQueueJobHandler', class_implements($classname))
        ) {
            throw new \UnexpectedValueException('The job handler for '.$jobIdentifier.' was not found');
        }

        return App::make($classname);
    }
}
