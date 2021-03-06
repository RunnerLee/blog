<?php
/**
 * Created by PhpStorm.
 * User: runner
 * Date: 16-10-29
 * Time: 下午4:05
 */

namespace App\Models\Traits;

use App\Models\Revisions;
use Auth;
use Illuminate\Support\Arr;

trait Revisionable
{

    protected $dirtyData = [];

//    protected $revisionExceptFields = [];
//
//    protected $revisionOnlyFields = [];
//
//    protected $revisionEnabled = true;
//
//    protected $revisionFormattedFieldValues = [];
//
//    protected $revisionFormattedFieldNames = [];


    public static function boot()
    {
        parent::boot();
    }


    public static function bootRevisionable()
    {
        static::created(function($model) {
            $model->postCreate();
        });

        static::updating(function($model) {
            $model->preUpdate();
        });

        static::updated(function($model) {
            $model->postUpdate();
        });

        static::deleted(function($model) {
            $model->postDelete();
        });
    }


    public function preUpdate()
    {
        $this->dirtyData = array_intersect_key($this->original, $this->getDirty());

        if (isset($this->revisionOnlyFields)) {
            foreach ($this->revisionOnlyFields as $key) {
                if(!isset($this->dirtyData[$key])) {
                    unset($this->dirtyData[$key]);
                }
            }
            return true;
        }

        if (isset($this->revisionExceptFields)) {
            foreach ($this->revisionExceptFields as $key) {
                if(isset($this->dirtyData[$key])) {
                    unset($this->dirtyData);
                }
            }
        }

        return true;
    }



    public function postDelete()
    {
        if(isset($this->forceDeleting) && !$this->forceDeleting) {
            $this->dirtyData = [
                'deleted_at' => null,
            ];

            return $this->postUpdate();
        }

        Revisions::create([
            'user_id'           => $this->lookupUserId(),
            'revisionable_type' => $this->getMorphClass(),
            'revisionable_id'   => $this->getKey(),
            'type'              => 'delete',
            'old_value'         => null,
            'new_value'         => null,
            'field'             => null,
        ]);

        return true;
    }



    public function postUpdate()
    {
        foreach($this->dirtyData as $key => $value) {
            Revisions::create([
                'user_id'           => $this->lookupUserId(),
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id'   => $this->getKey(),
                'type'              => 'update',
                'old_value'         => $value,
                'new_value'         => $this->getAttribute($key),
                'field'             => $key,
            ]);
        }

        $this->dirtyData = [];

        return true;
    }


    public function postCreate()
    {
        Revisions::create([
            'user_id'           => $this->lookupUserId(),
            'revisionable_type' => $this->getMorphClass(),
            'revisionable_id'   => $this->getKey(),
            'type'              => 'create',
            'old_value'         => null,
            'new_value'         => null,
            'field'             => null,
        ]);

        return true;
    }


    protected function lookupUserId()
    {
        if(Auth::check()) {
            return Auth::user()->getAuthIdentifier();
        }

        return null;
    }
}