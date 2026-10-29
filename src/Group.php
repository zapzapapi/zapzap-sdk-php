<?php

declare(strict_types=1);

namespace ZapZap;

/**
 * /{instanceId}/group/* endpoints (WhatsApp groups).
 */
class Group
{
    /** @var Instance */
    private $ctx;

    public function __construct(Instance $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * POST /{id}/group/list - list groups the instance belongs to.
     *
     * @return mixed
     */
    public function list(?bool $getParticipants = null)
    {
        return $this->ctx->post('/group/list', ['getParticipants' => $getParticipants]);
    }

    /**
     * POST /{id}/group/info - details of a group.
     *
     * @return mixed
     */
    public function info(string $groupjid, ?bool $getInviteLink = null)
    {
        return $this->ctx->post('/group/info', [
            'groupjid' => $groupjid,
            'getInviteLink' => $getInviteLink,
        ]);
    }

    /**
     * POST /{id}/group/create - create a group.
     *
     * @param string[] $participants Numbers with DDI.
     * @return mixed
     */
    public function create(string $name, array $participants)
    {
        return $this->ctx->post('/group/create', [
            'name' => $name,
            'participants' => $participants,
        ]);
    }

    /**
     * POST /{id}/group/leave - leave a group.
     *
     * @return mixed
     */
    public function leave(string $groupjid)
    {
        return $this->ctx->post('/group/leave', ['groupjid' => $groupjid]);
    }

    /**
     * POST /{id}/group/updateParticipants - add/remove/promote/demote members.
     *
     * @param string   $action       add|remove|promote|demote
     * @param string[] $participants  Numbers with DDI.
     * @return mixed
     */
    public function updateParticipants(string $groupjid, string $action, array $participants)
    {
        return $this->ctx->post('/group/updateParticipants', [
            'groupjid' => $groupjid,
            'action' => $action,
            'participants' => $participants,
        ]);
    }

    /**
     * POST /{id}/group/updateName - rename a group.
     *
     * @return mixed
     */
    public function updateName(string $groupjid, string $name)
    {
        return $this->ctx->post('/group/updateName', [
            'groupjid' => $groupjid,
            'name' => $name,
        ]);
    }

    /**
     * POST /{id}/group/updateDescription - set a group's description.
     *
     * @return mixed
     */
    public function updateDescription(string $groupjid, string $description)
    {
        return $this->ctx->post('/group/updateDescription', [
            'groupjid' => $groupjid,
            'description' => $description,
        ]);
    }

    /**
     * POST /{id}/group/join - join a group by invite code.
     *
     * @return mixed
     */
    public function join(string $invitecode)
    {
        return $this->ctx->post('/group/join', ['invitecode' => $invitecode]);
    }

    /**
     * POST /{id}/group/inviteInfo - inspect a group invite.
     *
     * @return mixed
     */
    public function inviteInfo(string $invitecode)
    {
        return $this->ctx->post('/group/inviteInfo', ['invitecode' => $invitecode]);
    }
}
