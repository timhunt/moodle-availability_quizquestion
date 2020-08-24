# Restriction by single quiz question # [![Build Status](https://travis-ci.com/timhunt/moodle-availability_quizquestion.svg?branch=main)](https://travis-ci.com/timhunt/moodle-availability_quizquestion)

This is a Moodle conditional availability rule, which makes it possile
to show or hide another resource, based on the state of one particular question
in one quiz. So, if you want show a particular help document to students
who got Question 2 in the quiz wrong, well this plugin lets you do that.


## To install ##

Once this is published, you will be able to install it from
https://moodle.org/plugins/availability_quizquestion.

Alternatively you can install using git. Run these commands in the root of your
Moodle site:

    git clone https://github.com/timhunt/moodle-availability_quizquestion.git availability/condition/quizquestion
    echo '/availability/condition/quizquestion/' >> .git/info/exclude

Then visit Admin -> Notifications to complete the installation.


## Credits ##

This plugin was created by Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner
and Alex Keiller at #MootDACH 2020 Dev Camp.


## License ##

2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner, Alex Keiller

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
