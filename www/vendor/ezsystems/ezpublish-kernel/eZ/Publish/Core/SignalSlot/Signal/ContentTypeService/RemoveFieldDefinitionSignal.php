<?php
/**
 * RemoveFieldDefinitionSignal class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.07.0
 */

namespace eZ\Publish\Core\SignalSlot\Signal\ContentTypeService;

use eZ\Publish\Core\SignalSlot\Signal;

/**
 * RemoveFieldDefinitionSignal class
 * @package eZ\Publish\Core\SignalSlot\Signal\ContentTypeService
 */
class RemoveFieldDefinitionSignal extends Signal
{
    /**
     * ContentTypeDraftId
     *
     * @var mixed
     */
    public $contentTypeDraftId;

    /**
     * FieldDefinitionId
     *
     * @var mixed
     */
    public $fieldDefinitionId;
}
