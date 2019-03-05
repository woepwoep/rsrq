<?php

namespace RedSeadog\Rsrq\Domain\Model;

class Query
{

    /**
     * The query
     *
     * @var string
     **/
    protected $query = '';


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
