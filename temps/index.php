#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-openshift-resources - grading/setup script for DO180
#
# SYNOPSIS
#     lab-openshift-resources {start|finish}
#
#        start   - configures the environment at the start
#                  of a lab or exercise.
#        finish  - executes any administrative tasks after
#                  completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script is for the GE 'Deploying a Database Server on OpenShift'
#
# CHANGELOG
#   * Fri Feb 15 2019 Michael Jarrett <mjarrett@redhat.com>
#   - Updated to version 4.0
#   * Mon Jun 11 2018 Artur Glogowski <aglogows@redhat.com>
#   - Updated to version 3.9
#   * Fri Apr 7 2017 Ravi Srinivasan <ravis@redhat.com>
#   - Updated for DO180
#   - Removed solve verb
#
#   * Sun Jan 10 2016 Zach Gutterman <zgutterm@redhat.com>
#   - original code from DO276

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin

# Initialize and set some variables
run_as_root='true'
this='openshift-resources'
target='workstation'
title='Guided Exercise: Deploying a Database Server on OpenShift'
# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and reset of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  ocp4_is_cluster_up

  pad " · Ensuring the '${RHT_OCP4_DEV_USER}-mysql-openshift' project is absent"
  delete_project "${RHT_OCP4_DEV_USER}-mysql-openshift"
  success_if_equal $? 0
}



function lab_finish {
  print_header "Completing the ${title}"

  pad " · Deleting the '${RHT_OCP4_DEV_USER}-mysql-openshift' project"
  delete_project "${RHT_OCP4_DEV_USER}-mysql-openshift"
  success_if_equal $? 0

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-openshift-review - DO180 Deploy Containerized App on OpenShift
#
# SYNOPSIS
#     lab-openshift {start|grade|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        grade   - checks that containers and images have been created successfully.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script is for lab 6.11
#
# CHANGELOG
#   * Wen Feb 20 2019 Eduardo Ramirez <eramirez@redhat.com>
#   - Updated to OCP 4.0
#   * Mon Jun 18 2018 Artur Glogowski <aglogows@redhat.com>
#   - updated to version 3.9
#   * Mon Apr 10 2017 Jim Rigsbee <jrigsbee@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin

# Initialize and set some variables
run_as_root='true'
this='openshift-review'
target='workstation'
title='Lab: Deploying Containerized Applications on OpenShift'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start grade finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and grading of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  ocp4_is_cluster_up

  pad " · Ensuring the '${RHT_OCP4_DEV_USER}-ocp' project does not exist"
  delete_project "${RHT_OCP4_DEV_USER}-ocp"
  if [ $? -eq 0 ]; then
    print_SUCCESS
  else
    print_FAIL
  fi

  print_line
}

function lab_grade {
  print_header "Grading the student's work for the ${title}"

  ocp4_login_as_developer

  pad "Accessing the web application"
  local fqdn=$(find_route_fqdn2)
  curl -f -s --connect-timeout 1 "http://${fqdn}" | grep "Converting"
  if [ $? == "0" ]; then
    print_PASS
  else
    print_FAIL
    print_line "   --> Unable to access: $fqdn"
  fi

}

function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer

  pad " · Deleting the ${RHT_OCP4_DEV_USER}-ocp project"

  delete_project "${RHT_OCP4_DEV_USER}-ocp"
  if [ $? -eq 0 ]; then
    print_SUCCESS
  else
    print_FAIL
  fi

}

function find_route_fqdn2 {
  #local stmt=$(${oc} get route temps | tail -n1 | awk '{print $2}')
  local stmt=$(${oc} get route temps -o template --template='{{.spec.host}}')
  echo $stmt
}


function find_route_fqdn {
  local stmt=$(${oc} describe route temps | grep Host)
  IFS=':' read -ra FQDN <<< "$stmt"
  echo "${FQDN[1]}" | tr -d [:space:]
}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-openshift-routes - DO180 Exposing a Service as a Route
#
# SYNOPSIS
#     lab-openshift-routes {start|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures the initial state of the lab with lab and solution files.
#
# CHANGELOG
#   * Mon Feb 19 2019 Jordi Sola <jordisola@redhat.com>
#   - Updated to OCP.4
#   * Fri Jun 15 2018 Artur Glogowski <aglogows@redhat.com>
#   - updated to version 3.9
#   * Sun Apr 09 2017 Jim Rigsbee <jrigsbee@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin

# Initialize and set some variables
run_as_root='true'
this='openshift-routes'
target='workstation'
title='Guided Exercise: Exposing a Service as a Route'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and grading of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  ocp4_is_cluster_up

  pad " · Ensuring the '${RHT_OCP4_DEV_USER}-route' project does not exist"
  delete_project "${RHT_OCP4_DEV_USER}-route"
  success_if_equal $? 0
}


function lab_finish {
  print_header "Completing the ${title}"

  pad " · Removing OpenShift project '${RHT_OCP4_DEV_USER}-route'"
  delete_project "${RHT_OCP4_DEV_USER}-route"
  success_if_equal $? 0

}

function find_route_fqdn {
  local stmt=$(oc describe route xyz | grep Host)
  IFS=':' read -ra FQDN <<< "$stmt"
  echo "${FQDN[1]}" | tr -d [:space:]
}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-openshift-s2i - DO180 Creating a Containerized Application with Source-to-Image
#
# SYNOPSIS
#     lab-openshift-s2i {start|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures the initial state of the lab with lab and solution files.
#
# CHANGELOG
#   * Tue Dec 01 2020 Michael Phillips <miphilli@redhat.com>
#   - start function does not need to grab lab files as they are unnecessary and the exercise has been modified to not look at them.
#   * Thu Feb 14 2019 Eduardo Ramirez <eramirez@redhat.com>
#   - Updated to OCP 4.0
#   * Thu Jun 14 2018 Artur Glogowski
#   - updated to version 3.9
#   * Fri Apr 07 2017 Jim Rigsbee <jrigsbee@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin

# Initialize and set some variables
run_as_root='true'
this='openshift-s2i'
target='workstation'
title='Guided Excercise: Creating a Containerized Application with Source-to-Image'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

project_name=php-helloworld
git_repo="http://services.lab.example.com/${project_name}"

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and grading of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  pad " · Installing the tree command"
  sudo yum install -y tree
  success_if_equal $? 0

  ocp4_verify_local_clone_exist

  ocp4_is_cluster_up

  pad " · Ensuring the '${RHT_OCP4_DEV_USER}-s2i' project does not exist"
  delete_project "${RHT_OCP4_DEV_USER}-s2i"
  success_if_equal $? 0
}

function lab_finish {
  print_header "Completing the ${title}"



  ocp4_login_as_developer
  pad " · Deleting the '${RHT_OCP4_DEV_USER}-s2i' project"
  if [ $? -eq 0 ]; then
    delete_project "${RHT_OCP4_DEV_USER}-s2i"
    success_if_equal $? 0
  else
    print_FAIL
  fi

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-openshift-webconsole - DO180 Creating an Application with the Web Console
#
# SYNOPSIS
#     lab-openshift-webconsole {start|finish}
#
#        start  - configures the environment at the start of a lab or exercise.
#        finish - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures the initial state of the lab with lab and solution files.
#
# CHANGELOG
#   * Tue Feb 12 2019 Dan Kolepp <dkolepp@redhat.com>
#   - updated to v.4.0
#   - changed script verbs from (setup|cleanup), to (start|finish)
#   * Mon Jun 18 2018 Artur Glogowski <aglogows@redhat.com>
#   - updated to v.3.9
#   * Sun Apr 09 2017 Jim Rigsbee <jrigsbee@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin

# Initialize and set some variables
run_as_root='true'
this='openshift-webconsole'
target='workstation'
title='Guided Exercise: Creating an Application with the Web Console'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)



# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the start and finish of this exercise.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  ocp4_is_cluster_up

  pad " · Ensuring the '${RHT_OCP4_DEV_USER}-console' project does not exist"
  delete_project "${RHT_OCP4_DEV_USER}-console"
  if [ $? -eq 0 ]; then
    print_SUCCESS
  else
    print_FAIL
  fi

  #Creates more readable output
  print_line

}


function lab_finish {
  print_header "Completing the ${title}"

  pad " · Ensure the '${RHT_OCP4_DEV_USER}-console' project is deleted "
  delete_project "${RHT_OCP4_DEV_USER}-console"
  if [ $? -eq 0 ]; then
    print_SUCCESS
  else
    print_FAIL
  fi

  print_line
}


function find_consoler_port {
  local stmt=$(oc set env dc/router --list -n default | grep HTTP_PORT)
  IFS='=' read -ra ROUTER <<< "$stmt"
  echo "${ROUTER[1]}"
}

function find_console_fqdn {
  local stmt=$(oc describe route php-helloworld -n console | grep Host)
  IFS=':' read -ra FQDN <<< "$stmt"
  echo "${FQDN[1]}" | tr -d [:space:]
}
############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
