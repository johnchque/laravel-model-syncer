<?php

namespace johnchque\laravelModelSyncer\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;
use const ARRAY_FILTER_USE_KEY;

trait ModelSyncer {

    public $replace_uuids;

    /**
     * Load Model content recursively.
     *
     * @param Model $model
     *   The model to add to the values array.
     * @param array $relations
     *   The relations array with all Models relation fields.
     * @param array $values
     *   The loaded values.
     *
     * @return array
     */
    protected function loadModelRecursive(Model $model, array $relations, &$values = []): array {
        if (!isset($relations[$model->getTable()])) {
            return $values;
        }
        foreach ($relations[$model->getTable()] as $relation => $field) {
            if ($model->$relation()->exists()) {
                foreach ($model->$relation()->get() as $relation) {
                    $values[$relation->getTable()][$relation->uuid] = $this->normalize($relation);
                    $values[$relation->getTable()][$relation->uuid]['values'][$field] = $model->uuid;
                    $values[$relation->getTable()][$relation->uuid]['_depends'][] = $model->uuid;
                    $values = $this->loadModelRecursive($relation, $relations, $values);
                }
            }
        }
        return $values;
    }

    /**
     * Normalizes the given Model to remove metadata fields.
     *
     * @param Model $model
     *   The model to normalize.
     *
     * @return array
     */
    public function normalize(Model $model): array {
        $normalized = [
            '_type' => \get_class($model),
            '_table' => $model->getTable(),
            '_depends' => [],
        ];
        $attributes = $model->getFillable();
        $result = array_filter($model->toArray(), function ($v) use ($attributes) {
            return in_array($v, $attributes);
        }, ARRAY_FILTER_USE_KEY);

        if ($model instanceof SyncerOverrideInterface) {
            $model->overrideFields($result);
        }
        $normalized['values'] = $result;
        return $normalized;
    }

    /**
     * Normalizes the given Model to remove metadata fields.
     *
     * @param $normalized_models
     *   The normalized models to export.
     *
     * @return string
     */
    public function exportToZip(array $normalized_models, $subject_name = ''): string {
        $zip = new ZipArchive;
        $folder = 'public/media/' . now()->format('Ymd');
        $timestamp = now()->format('YmdHis');
        if ($subject_name) {
            $subject_name = \strtolower(preg_replace('/\s+/', '-', $subject_name));
        }
        $fileName = 'app/' . $folder . '/' . $timestamp . '-' . $subject_name . '.zip';
        if (Storage::makeDirectory($folder) && $zip->open(\storage_path($fileName), ZipArchive::CREATE) === TRUE) {
            foreach ($normalized_models as $parent_uuid => $related_models) {
                $zip->addEmptyDir($parent_uuid);
                foreach ($related_models as $model_type => $model_values) {
                    $zip->addEmptyDir($parent_uuid . '/' . $model_type);
                    foreach ($model_values as $uuid => $data) {
                        $encoded = Yaml::dump($data);
                        $zip->addFromString($parent_uuid . '/' . $model_type . '/' . $uuid . '.yml', $encoded);
                    }
                }
            }
            $zip->close();
        }
        return $folder . '/' . $timestamp . '-' . $subject_name . '.zip';
    }

    /**
     * Load Model content recursively.
     *
     * @param array $item
     *   The item values.
     */
    public function importModel(array $item) {
        $dependency_created = TRUE;
        if (!empty($item['_depends'])) {
            foreach ($item['_depends'] as $dependency) {
                if (!isset($this->replace_uuids[$dependency])) {
                    $dependency_created = FALSE;
                }
            }
        }

        if ($dependency_created) {
            $old_uuid = $item['values']['uuid'];
            unset($item['values']['uuid']);
            foreach ($item['values'] as $key => $value) {
                if (isset($this->replace_uuids[$value])) {
                    $item['values'][$key] = $this->replace_uuids[$value];
                }
            }
            $type = $item['_type'];
            $model = $type::create($item['values']);
            $this->replace_uuids[$old_uuid] = $model->id;
        }
    }

}
