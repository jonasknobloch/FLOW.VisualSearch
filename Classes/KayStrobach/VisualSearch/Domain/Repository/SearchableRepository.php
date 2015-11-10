<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 23.04.15
 * Time: 16:49
 */

namespace KayStrobach\VisualSearch\Domain\Repository;
use TYPO3\Flow\Error\Debugger;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Reflection\ObjectAccess;


/**
 * @Flow\Scope("singleton")
 */
class SearchableRepository extends Repository implements SearchableRepositoryInterface {
	/**
	 * spezifies the default search used in visualsearch with that repository
	 *
	 * @var string
	 */
	protected $defaultSearchName = NULL;

	/**
	 * helps to use the data objects of the search
	 *
	 * @Flow\Inject
	 * @var \KayStrobach\VisualSearch\Utility\MapperUtility
	 */
	protected $mapperUtility;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Function to aid KayStrobach.VisualSearch to find entries
	 *
	 * @param array $query
	 * @param string $term
	 * @param array $facetConfiguration
	 * @param array $searchConfiguration
	 * @return QueryResultInterface
	 */
	public function findBySearchTerm($query, $term = '', $facetConfiguration = array(), $searchConfiguration = array()) {
		$query = $this->createQuery();

		// restrict by number of records by term
		if (isset($facetConfiguration['labelProperty'])) {
			$query->matching(
				$query->like(
					$facetConfiguration['labelProperty'], '%' . $term . '%'
				)
			);
		}

		// set orderings
		if (isset($facetConfiguration['orderBy'])) {
			$query->setOrderings(
				array($facetConfiguration['orderBy']  => QueryInterface::ORDER_ASCENDING)
			);
		}

		$this->systemLogger->log('findBySearchTerm:' . Debugger::renderDump($query->getConstraint(), 2 ,TRUE), LOG_ALERT);

		return $query->execute();
	}

	/**
	 * function to filter the repository result by a given query
	 * this function should be used to display the filtered result list
	 *
	 * @param array $query
	 * @param null $searchName
	 * @return QueryResultInterface
	 */
	public function findByQuery($query, $searchName = NULL) {
		if ($searchName === NULL) {
			if (isset($this->defaultSearchName)) {
				$searchName = $this->defaultSearchName;
			} else {
				$searchName = $this->getEntityClassName();
			}
		}

		$demands = array();
		$queryObject = $this->createQuery();

		// get all the other filters
		if (method_exists($this, 'initializeFindByQuery')) {
			$demands = $this->initializeFindByQuery($queryObject, $searchName);
		}

		// merge demands from VisualSearch.yaml and the
		$demands = array_merge($demands, $this->mapperUtility->buildQuery($searchName, $query, $queryObject));

		if(count($demands) > 0) {
			$queryObject->matching(
				$queryObject->logicalAnd(
					$demands
				)
			);
		}

		$this->systemLogger->log('findByQuery:' . Debugger::renderDump($queryObject->getConstraint(), 2 ,TRUE), LOG_ALERT);

		return $queryObject->execute();
	}

	/**
	 * add demands into the query
	 *
	 * @param \TYPO3\Flow\Persistence\Doctrine\Query $queryObject
	 * @param string $searchName
	 *
	 * @return array
	 */
	protected function initializeFindByQuery($queryObject, $searchName) {
		return array();
	}
}