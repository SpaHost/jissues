<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model to get data for the issue list view
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerModelIssues extends JModelTrackerList
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'com_tracker.issues';

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from($db->quoteName('#__issues', 'a'));

		// Join over the status.
		$query->select('s.status AS status_title, s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		$filter = $this->state->get('filter.project');

		if ($filter)
		{
			$query->where($db->quoteName('a.project_id') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('list.filter');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(JString::strtolower($filter), true) . '%', false);

			// Check the author, title, and publish_up fields
			$query->where('(' . $db->quoteName('a.title') . ' LIKE ' . $filter . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter . ')');
		}

		$status = $this->state->get('filter.status');
		if ($status)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $status);
		}

		// TODO: Implement filtering and join to other tables as added

		$ordering  = $db->escape($this->state->get('list.ordering', 'a.id'));
		$direction = $db->escape($this->state->get('list.direction', 'ASC'));
		$query->order($ordering . ' ' . $direction);

		return $query;
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.0
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->state->get('filter.priority');
		$id .= ':' . $this->state->get('filter.status');
		$id .= ':' . $this->state->get('list.filter');

		return parent::getStoreId($id);
	}

	/**
	 * Load the model state.
	 *
	 * @return  JRegistry  The state object.
	 *
	 * @since   1.0
	 */
	protected function loadState()
	{
		$this->state = new JRegistry;
		$session = JFactory::getSession();

		$input = JFactory::getApplication()->input;

		$projectId = $input->getUint('project_id');

		if(!$projectId)
		{
			$projectId = $session->get('tracker.project_id');
		}

		$session->set('tracker.project_id', $projectId);

		$this->state->set('filter.project', $projectId);

		$this->state->set('list.ordering', $input->get('filter_order', 'a.id'));

		$listOrder = $input->get('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}
		$this->state->set('list.direction', $listOrder);

		$this->state->set('filter.priority', $input->getUint('priority', 3));

		$this->state->set('filter.status', $input->getUint('filter-status'));

		// Optional filter text
		$this->state->set('list.filter', $input->getString('filter-search'));

		// List state information.
		parent::loadState();
	}

	/**
	 * Get a project by its id.
	 *
	 * @todo move to its own model.
	 *
	 * @return mixed|null
	 */
	public function getProject()
	{
		$id = JFactory::getApplication()->input->getUint('project_id', $this->state->get('filter.project'));

		if (!$id)
		{
			return null;
		}

		$db = JFactory::getDbo();

		$project = $this->db->setQuery(
			$this->db->getQuery(true)
				->from('#__tracker_projects')
				->select('*')
				->where($db->qn('project_id') . '=' . (int) $id)
		)->loadObject();

		return $project;
	}
}
