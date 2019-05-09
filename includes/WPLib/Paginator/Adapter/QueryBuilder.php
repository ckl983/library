<?php
/**
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Paginator\Adapter;

use Phalcon\Paginator\Adapter\QueryBuilder as PhalconQueryBuilder;

class QueryBuilder extends PhalconQueryBuilder
{

    /**
     * Returns a slice of the resultset to show in the pagination
     */
    public function getPaginate()
	{
		$originalBuilder = $this->_builder;

		/**
         * We make a copy of the original builder to leave it as it is
         */
		$builder = clone $originalBuilder;

		/**
         * We make a copy of the original builder to count the total of records
         */
		$totalBuilder = clone $builder;

		$limit = $this->_limitRows;
		$numberPage = (int) $this->_page;

		if (!$numberPage) {
			$numberPage = 1;
		}

        $number = $limit * ($numberPage - 1);

		/**
         * Set the limit clause avoiding negative offsets
         */
		if ($number < $limit) {
            $builder->limit($limit);
		} else {
            $builder->limit($limit, $number);
		}

		$query = $builder->getQuery();

		if ($numberPage == 1) {
            $before = 1;
		} else {
            $before = $numberPage - 1;
		}

		/**
         * Execute the query an return the requested slice of data
         */
		$items = $query->execute();

		/**
         * Change the queried columns by a COUNT(*)
         */
		$totalBuilder->columns("COUNT(*) [rowcount]");

		/**
         * Change 'COUNT()' parameters, when the query contains 'GROUP BY'
         */
		$groups = $totalBuilder->getGroupBy();
		if (!empty($groups)) {
            if (is_array($groups)) {
                $groupColumn = implode(", ", $groups);
			} else {
                $groupColumn = $groups;
			}
			$totalBuilder->groupBy(null)->columns(["COUNT(DISTINCT ".$groupColumn.") AS rowcount"]);
		}

		/**
         * Remove the 'ORDER BY' clause, PostgreSQL requires this
         */
		$totalBuilder->orderBy(null);

		/**
         * Obtain the PHQL for the total query
         */
		$totalQuery = $totalBuilder->getQuery();

		/**
         * Obtain the result of the total query
         */
		$result = $totalQuery->execute();
        $row = $result->getFirst();
        $rowcount = $row ? intval($row->rowcount) : 0;
        $totalPages = intval(ceil($rowcount / $limit));

		if ($numberPage < $totalPages) {
            $next = $numberPage + 1;
		} else {
            $next = $totalPages;
		}

		$page = new \stdClass();
        $page->items = $items;
        $page->first = 1;
        $page->before = $before;
        $page->current = $numberPage;
        $page->last = $totalPages;
        $page->next = $next;
        $page->total_pages = $totalPages;
        $page->total_items = $rowcount;
        $page->limit = $this->_limitRows;

		return $page;
	}

}