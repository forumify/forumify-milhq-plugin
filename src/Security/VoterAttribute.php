<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security;

enum VoterAttribute: string
{
    case AfterActionReportManage = 'MILHQ_AFTER_ACTION_REPORT_MANAGE';
    case CourseClassManage = 'MILHQ_COURSE_CLASS_MANAGE';
    case FormSubmissionCreate = 'MILHQ_FORM_SUBMISSION_CREATE';
    case FormSubmissionView = 'MILHQ_FORM_SUBMISSION_VIEW';
    case MissionManage = 'MILHQ_MISSION_MANAGE';
    case MissionRsvpCreate = 'MILHQ_MISSION_RSVP_CREATE';
    case MissionRsvpDelete = 'MILHQ_MISSION_RSVP_DELETE';
}
