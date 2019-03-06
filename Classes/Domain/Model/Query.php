<?php

namespace RedSeadog\Rsrq\Domain\Model;

class Query
{

    /**
     * The query title
     *
     * @var string
     **/
    protected $title = '';

    /**
     * The query description
     *
     * @var string
     **/
    protected $description = '';

    /**
     * The query itself
     *
     * @var string
     **/
    protected $query = '';


    /**
     * Sets the query title
     *
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Gets the query title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the query description
     *
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Gets the query description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the content of the query
     *
     * @param string $query
     */
    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    /**
     * Gets the content of the query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

}
