<?php
/**
 * File containing a DoctrineDatabase Location visibility sort clause handler class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.07.0
 */

namespace eZ\Publish\Core\Persistence\Legacy\Content\Search\Location\Gateway\SortClauseHandler\Location;

use eZ\Publish\Core\Persistence\Legacy\Content\Search\Common\Gateway\SortClauseHandler;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Persistence\Database\SelectQuery;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class Visibility extends SortClauseHandler
{
    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return boolean
     */
    public function accept( SortClause $sortClause )
    {
        return $sortClause instanceof SortClause\Location\Visibility;
    }

    /**
     * Apply selects to the query
     *
     * Returns the name of the (aliased) column, which information should be
     * used for sorting.
     *
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     * @param int $number
     *
     * @return string
     */
    public function applySelect( SelectQuery $query, SortClause $sortClause, $number )
    {
        $query
            ->select(
                $query->alias(
                    $this->dbHandler->quoteColumn(
                        'is_invisible',
                        'ezcontentobject_tree'
                    ),
                    $column = $this->getSortColumnName( $number )
                )
            );

        return $column;
    }
}
