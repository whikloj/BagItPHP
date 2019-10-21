<?php
/**
 * This is a PHP implementation of the {@link
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really,
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for
 * PHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

namespace ScholarsLab\BagIt;

/**
 * Constants for use through out our BagIt impl.
 * @package ScholarsLab\BagIt
 * @author Jared Whiklo <jwhiklo@gmail.com>
 * @since 2019-10-20
 */
class BagItConstants
{

    /**
     * Bag-info fields that MUST not be repeated (in lowercase).
     */
    const BAG_INFO_MUST_NOT_REPEAT = array(
        'payload-oxum'
    );

    /**
     * Reserved element names for Bag-info fields.
     */
    const BAG_INFO_RESERVED_ELEMENTS = array(
        'source-organization' => 'Source-Organization',
        'organization-address' => 'Organization-Address',
        'contact-name' => 'Contact-Name',
        'contact-phone' => 'Contact-Phone',
        'contact-email' => 'Contact-Email',
        'external-description' => 'External-Description',
        'bagging-date' => 'Bagging-Date',
        'external-identifier' => 'External-Identifier',
        'payload-oxum' => 'Payload-Oxum',
        'bag-size' => 'Bag-Size',
        'bag-group-identifier' => 'Bag-Group-Identifier',
        'bag-count' => 'Bag-Count',
        'internal-sender-identifier' => 'Internal-Sender-Identifier',
        'internal-sender-description' => 'Internal-Sender-Description',
    );
}
