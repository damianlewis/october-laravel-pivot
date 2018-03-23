<?php

namespace DamianLewis\October\Pivot\Relations;

use October\Rain\Database\Collection;
use October\Rain\Database\Model;
use October\Rain\Database\Relations\BelongsToMany;

class BelongsToManyCustom extends BelongsToMany
{
    /**
     * {@inheritDoc}
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        if (method_exists($this->getParent(), 'changeAttributes')) {
            list($id, $attributes) = $this->getParent()->changeAttributes($this->getRelationName(), $id, $attributes);
        }

        list($idsOnly, $idsAttributes) = $this->getIdsWithAttributes($id, $attributes);

        $this->parent->fireModelEvent('pivotAttaching', true, $this->getRelationName(), $idsOnly, $idsAttributes);
        parent::attach($id, $attributes, $touch);
        $this->parent->fireModelEvent('pivotAttached', false, $this->getRelationName(), $idsOnly, $idsAttributes);
    }

    /**
     * {@inheritDoc}
     */
    public function detach($ids = null, $touch = true)
    {
        if (is_null($ids)) {
            $ids = $this->query->pluck($this->query->qualifyColumn($this->relatedKey))->toArray();
        }

        list($idsOnly) = $this->getIdsWithAttributes($ids);

        $this->parent->fireModelEvent('pivotDetaching', true, $this->getRelationName(), $idsOnly);
        return parent::detach($ids, $touch);
        $this->parent->fireModelEvent('pivotDetached', false, $this->getRelationName(), $idsOnly);
    }

    /**
     * {@inheritDoc}
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        list($idsOnly, $idsAttributes) = $this->getIdsWithAttributes($id, $attributes);

        $this->parent->fireModelEvent('pivotUpdating', true, $this->getRelationName(), $idsOnly, $idsAttributes);
        return parent::updateExistingPivot($id, $attributes, $touch);
        $this->parent->fireModelEvent('pivotUpdated', false, $this->getRelationName(), $idsOnly, $idsAttributes);
    }

    /**
     * Cleans the ids and ids with attributes
     * Returns an array with and array of ids and array of id => attributes
     *
     * @param  mixed $id
     * @param  array $attributes
     *
     * @return array
     */
    protected function getIdsWithAttributes($id, $attributes = [])
    {
        $ids = [];

        if ($id instanceof Model) {
            $ids[$id->getKey()] = $attributes;
        } elseif ($id instanceof Collection) {
            foreach ($id as $model) {
                $ids[$model->getKey()] = $attributes;
            }
        } elseif (is_array($id)) {
            foreach ($id as $key => $attributesArray) {
                if (is_array($attributesArray)) {
                    $ids[$key] = array_merge($attributes, $attributesArray);
                } else {
                    $ids[$attributesArray] = $attributes;
                }
            }
        } elseif (is_int($id) || is_string($id)) {
            $ids[$id] = $attributes;
        }

        $idsOnly = array_keys($ids);

        return [$idsOnly, $ids];
    }
}
