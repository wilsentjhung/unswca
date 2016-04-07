#!/usr/bin/python

import urllib2
import re
#from bs4 import BeautifulSoup

allCourseCode = set()
codePattern = re.compile(r"[A-Z]{4}[0-9]{4}")
prereqSentence = re.compile(r"<p>[pP]re.*?<\/p>")
prereqOnlySentence = re.compile(r"<p>([pP]re.*?)([pP]requisite)?([cC]o-?[Rr]eq.*?)?([Ee]xcl.*?)?<\/p>")
exclOnlySentence = re.compile(r"<p>[pP]re.*?([Ee]xcl.*?)<\/p>")

f = open("pre_reqs.sql", "w")
f.write("DROP TABLE IF EXISTS pre_reqs;\n")
f.write("CREATE TABLE pre_reqs (course_code text, career text, pre_req_condtions text, norm_pre_req_conditions text);\n")

ugUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode = re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

ugUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

for hc in subjectCode:
	career = "UG"
	if "postgraduate" in hc:
		career = "PG"

	url2 = hc
	try:
		codeInUrl = re.findall(codePattern, url2)
		html2 = urllib2.urlopen(url2).read()
		courseCode = re.findall(prereqSentence, html2)

		if courseCode:
			prereq = prereqOnlySentence.search(courseCode[0]).group(1)
			prereqCondition = prereq
			prereqCondition = re.sub(r"\'", "\'\'", prereqCondition, flags=re.IGNORECASE)

			#remove prereq word
			prereq = re.sub(r"Pre(.*?:|requisite)", "(", prereq, flags=re.IGNORECASE)

			#change to ands
			prereq = re.sub(r"\sAND\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\s&\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\sincluding\s", " && ", prereq, flags=re.IGNORECASE)

			#comma can mean and/or
			prereq = re.sub(r",\s*or", " || ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r",", " && ", prereq, flags=re.IGNORECASE)

			#change to ors
			prereq = re.sub(r';', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\sOR\s', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\/', ' || ', prereq, flags=re.IGNORECASE)

			#change to uoc
			prereq = re.sub(r'\s*uc\b', ' UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'uoc\b', ' UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'unit.*? of credit.*?\b', ' UOC ', prereq, flags=re.IGNORECASE)

			#remove unnecessary words
			prereq = re.sub(r'\.', '', prereq, flags=re.IGNORECASE)

			#change [] to ()
			prereq = re.sub(r'\[', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\]', ')', prereq, flags=re.IGNORECASE)



			prereq = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'approval from the School', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'school approval', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'Enrolment in program ([0-9]+)', r'\1', prereq, flags=re.IGNORECASE)

			prereq = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', "(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'([0-9]+)\s+UOC\s+at Level 1', r'\1_UOC_LEVEL_1', prereq, flags=re.IGNORECASE)

			prereq = re.sub(r'stream', '', prereq, flags=re.IGNORECASE)

			#cleanup
			prereq = re.sub(r'&&\s*&&$', '&&', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'&&\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\|\|\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq += ")"
			prereq = re.sub(r'\(\s*\)', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\(\s*', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*\)', ')', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s\s+', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\'', '\'\'', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'^\s+$', '', prereq, flags=re.IGNORECASE)
			#print prereq


			#manual
			if (codeInUrl[0] ==  "ARCH1302"):
				prereq = "(ARCH1202 && ARCH1301)"
			elif (codeInUrl[0] ==  "ARCH1395"):
				prereq = "(ARCH1394 && ARCH1384)"
			elif (codeInUrl[0] == "ARTS2006"):
				prereq = "(MUSC1704 || MUSC1705 || MUSC1706 || ARTS1005 || 3425 || 3426 || 3427 || 3448 || 3449)"




			

			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_condtions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, prereqCondition, prereq, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_condtions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, codeInUrl[0], career))

	except:
		print codeInUrl,
		print "No Handbook Entry"

f.close()