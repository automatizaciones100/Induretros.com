<?php
/**
 * @license GPL-2.0
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder;

use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\Aggregate;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\CRUD;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\FromClause;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\GroupByStatement;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\HavingClause;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\JoinClause;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\LimitStatement;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\MetaQuery;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\OffsetStatement;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\OrderByStatement;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\SelectStatement;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\TablePrefix;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\UnionOperator;
use KadenceWP\KadenceShopKit\StellarWP\DB\QueryBuilder\Concerns\WhereClause;

/**
 * @since 1.0.0
 */
class QueryBuilder {
	use Aggregate;
	use CRUD;
	use FromClause;
	use GroupByStatement;
	use HavingClause;
	use JoinClause;
	use LimitStatement;
	use MetaQuery;
	use OffsetStatement;
	use OrderByStatement;
	use SelectStatement;
	use TablePrefix;
	use UnionOperator;
	use WhereClause;

	/**
	 * @return string
	 */
	public function getSQL() {
		$sql = array_merge(
			$this->getSelectSQL(),
			$this->getFromSQL(),
			$this->getJoinSQL(),
			$this->getWhereSQL(),
			$this->getGroupBySQL(),
			$this->getHavingSQL(),
			$this->getOrderBySQL(),
			$this->getLimitSQL(),
			$this->getOffsetSQL(),
			$this->getUnionSQL()
		);

		// Trim double spaces added by DB::prepare
		return str_replace(
			[ '   ', '  ' ],
			' ',
			implode( ' ', $sql )
		);
	}
}
