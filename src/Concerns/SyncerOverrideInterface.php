<?php

namespace johnchque\laravelModelSyncer\Concerns;

interface SyncerOverrideInterface {

    /**
     * Field overrider for exporting models.
     *
     * When models are exported and use ids for referencing data without being
     * foreign keys, we should allow them to have those fields overridden so
     * when importing or duplicating the fields are linked properly to the newly
     * created models. Send the resultant fields from the normalize method and
     * apply any overriding.
     *
     * @param array &$result
     *   An associative array containing the fields that the syncer will export.
     */
    function overrideFields(array &$result);

}
