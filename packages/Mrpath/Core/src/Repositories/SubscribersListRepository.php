<?php

namespace Mrpath\Core\Repositories;

use Mrpath\Core\Eloquent\Repository;
use Prettus\Repository\Traits\CacheableRepository;

class SubscribersListRepository extends Repository
{
    use CacheableRepository;

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Mrpath\Core\Contracts\SubscribersList';
    }

    /**
     * Delete a slider item and delete the image from the disk or where ever it is
     *
     * @param  int  $id
     * @return bool
     */
    public function destroy($id)
    {
        return $this->model->destroy($id);
    }
}