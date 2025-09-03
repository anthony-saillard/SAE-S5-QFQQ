import { AssignmentsApiService } from 'src/modules/resources/services/AssignmentsApiService.js'
import { CourseTeachersApiService } from 'src/modules/resources/services/CourseTeachersApiService.js'
import { CourseTypesApiService } from 'src/modules/settings/services/CourseTypesApiService.js'
import { FormationsApiService } from 'src/modules/formations/services/FormationsApiService.js'
import { GroupsApiService } from 'src/modules/settings/services/GroupsApiService.js'
import { PedagogicalInterruptionsApiService } from 'src/modules/settings/services/PedagogicalInterruptionsApiService.js'
import { ResourcesApiService } from 'src/modules/resources/services/ResourcesApiService.js'
import { SchoolYearsApiService } from 'src/modules/settings/services/SchoolYearsApiService.js'
import { SemestersApiService } from 'src/modules/formations/services/SemestersApiService.js'
import { SubResourcesApiService } from 'src/modules/resources/services/SubResourcesApiService.js'
import { UsersApiService } from 'src/modules/users/UsersApiService.js'
import { AnnotationsApiService } from 'src/modules/resources/services/AnnotationsApiService.js'

export const ApiService = {
  formations: FormationsApiService,
  semesters: SemestersApiService,
  assignments: AssignmentsApiService,
  teachers: CourseTeachersApiService,
  resources: ResourcesApiService,
  subResources: SubResourcesApiService,
  courseTypes: CourseTypesApiService,
  groups: GroupsApiService,
  pedagogicalInterruptions: PedagogicalInterruptionsApiService,
  schoolYears: SchoolYearsApiService,
  users: UsersApiService,
  annotations: AnnotationsApiService,

  clearAllCaches() {
    this.formations.clearCache()
    this.semesters.clearCache()
    this.assignments.clearCache()
    this.teachers.clearCache()
    this.resources.clearCache()
    this.subResources.clearCache()
    this.courseTypes.clearCache()
    this.pedagogicalInterruptions.clearCache()
    this.schoolYears.clearCache()
    this.users.clearCache()
    this.annotations.clearCache()
  }
}
