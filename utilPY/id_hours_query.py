# Autor: Daniel Leaman
# Purpose: take the lifetime hours from the initial data and format it in a
#          way that can be put into a query (id, minutes)
# Q&A:
# Q: why didn't I just do it from PY?
# A: idk, I am better at writing sql than py
# Q: why did you use this to write the bulk of the query?
# A: sump cost 

list = """aa01,99hr 4m
aa02,0hr
aa03,3hr 30m
aa04,4hr 30m
aa05,26hr
aa06,0hr
aa07,19hr 30m
aa08,9hr 45m
aa09,2hr 30m
aa10,3hr 30m
aa11,0hr
aa12,0hr
aa13,0hr
aa14,2hr 12m
aa15,8hr 32m
aa16,4hr 30m
aa17,2hr
aa18,4hr
aa19,0hr
aa20,0hr
aa21,6hr
aa22,3hr
aa23,2hr 30m
aa24,0hr
aa25,0hr
aa26,40hr 51m
aa27,0hr
aa28,1hr
aa29,0hr
aa30,12hr
aa31,4hr
aa32,4hr 15m
aa33,3hr
aa34,56hr 25m
aa35,46hr 5m
aa36,0hr
aa37,1hr 30m
aa38,5hr 15m
aa39,0hr
aa40,33hr 30m
aa41,0hr
aa42,11hr 30m
aa43,12hr
aa44,1039hr
aa45,39hr 10m
aa46,13hr 50m
aa47,0hr
aa48,5hr 50m
aa49,2hr
aa50,0hr
aa51,6hr
aa52,0hr
aa53,0hr
aa54,15hr
aa55,284hr 55m
aa56,12hr
aa57,76hr
aa58,7hr 37m
aa59,898hr 40m
aa60,0hr
aa61,4hr 30m
aa62,14hr 9m
aa63,1hr 49m
aa64,31hr 10m
aa65,35hr 20m
aa66,40hr 6m
aa67,0hr
aa68,10hr 30m
aa69,0hr
aa70,474hr 18m
aa71,5hr 3m
aa72,0hr
aa73,0hr
aa74,49hr 34m
aa75,0hr
aa76,0hr
aa77,0hr
aa78,40hr
aa79,4hr
aa80,0hr
aa81,0hr
aa82,0hr
aa83,6hr 30m
aa84,3hr 48m
aa85,20hr
aa86,19hr 15m
aa87,20hr
aa88,3hr 15m
aa89,0hr
aa90,0hr
aa91,56hr 45m
aa92,0hr
aa93,202hr 24m
aa94,182hr 59m
aa95,30hr 21m
aa96,143hr 59m
aa97,21hr 33m
aa98,13hr 45m
aa99,0hr
ab00,17hr
ab01,874hr 50m
ab02,0hr
ab03,1hr
ab04,7hr
ab05,4hr 30m
ab06,0hr
ab07,19hr 30m
ab08,28hr
ab09,25hr 34m
ab10,10hr
ab11,22hr 24m
ab12,269hr 3m
ab13,0hr
ab14,0hr
ab15,0hr
ab16,708hr 48m
ab17,0hr
ab18,6hr 44m
ab19,6hr 15m
ab20,6hr 30m
ab21,25hr 50m
ab22,8hr
ab23,3hr 30m
ab24,1hr
ab25,7hr 51m
ab26,8hr 30m
ab27,0hr
ab28,2hr
ab29,6hr 15m
ab30,6hr
ab31,7hr
ab32,93hr
ab33,4hr
ab34,0hr
ab35,35hr 1m
ab36,0hr
ab37,27hr 3m
ab38,117hr 28m
ab39,0hr
ab40,2hr
ab41,6hr
ab42,62hr 40m
ab43,72hr 38m
ab44,0hr
ab45,110hr 20m
ab46,4hr
ab47,16hr
ab48,0hr
ab49,60hr
ab50,0hr
ab51,3hr
ab52,0hr
ab53,4hr 30m
ab54,0hr
ab55,22hr 15m
ab56,6hr 10m
ab57,12hr 32m
ab58,100hr 25m
ab59,236hr 32m
ab60,0hr
ab61,0hr
ab62,0hr
ab63,5hr 30m
ab64,8hr
ab65,260hr 45m
ab66,5hr
ab67,26hr 25m
ab68,0hr
ab69,13hr 45m
ab70,74hr
ab71,8hr
ab72,0hr
ab73,15hr 40m
ab74,325hr 42m
ab75,747hr 39m
ab76,7hr
ab77,0hr
ab78,11hr 30m
ab79,0hr
ab80,3hr
ab81,0hr
ab82,24hr 10m
ab83,5hr 30m
ab84,0hr
ab85,4hr 30m
ab86,20hr 46m
ab87,43hr 22m
ab88,24hr 2m
ab89,19hr 30m
ab90,15hr 3m
ab91,11hr 28m
ab92,0hr
ab93,46hr 21m
ab94,22hr 35m
ab95,21hr 14m
ab96,4hr 3m
ab97,36hr
ab98,6hr 3m
ab99,158hr 4m
ac00,0hr
ac01,0hr
ac02,8hr 15m
ac03,0hr
ac04,437hr 22m
ac05,4hr 30m
ac06,18hr 10m
ac07,2hr
ac08,2hr
ac09,0hr
ac10,0hr
ac11,118hr 15m
ac12,4hr
ac13,2hr
ac14,6hr 30m
ac15,0hr
ac16,0hr
ac17,27hr
ac18,5hr
ac19,6hr
ac20,7hr 30m
ac21,5hr 30m
ac22,46hr 34m
ac23,0hr
ac24,0hr
ac25,0hr
ac26,17hr 34m
ac27,12hr
ac28,3hr
ac29,15hr 29m
ac30,237hr
ac31,0hr
ac32,20hr 5m
ac33,0hr
ac34,3hr 49m
ac35,5hr 30m
ac36,8hr
ac37,3hr
ac38,6hr
ac39,19hr
ac40,0hr
ac41,0hr
ac42,75hr 55m
ac43,0hr
ac44,0hr
ac45,0hr
ac46,0hr
ac47,99hr 30m
ac48,65hr
ac49,0hr
ac50,25hr 57m
ac51,5hr
ac52,580hr 57m
ac53,4hr
ac54,203hr 10m
ac55,0hr
ac56,610hr 44m
ac57,277hr 59m
ac58,9hr 58m
ac59,6hr 30m
ac60,138hr 31m
ac61,3hr
ac62,4hr
ac63,0hr
ac64,0hr
ac65,12hr 30m
ac66,0hr
ac67,12hr 18m
ac68,2hr
ac69,8hr
ac70,12hr 9m
ac71,0hr
ac72,0hr
ac73,0hr
ac74,63hr 10m
ac75,0hr
ac76,155hr 25m
ac77,4hr
ac78,8hr 30m
ac79,0hr
ac80,42hr 41m
ac81,2hr 59m
ac82,4hr
ac83,29hr
ac84,0hr
ac85,100hr
ac86,8hr 3m
ac87,0hr
ac88,200hr
ac89,29hr 11m
ac90,0hr
ac91,128hr 22m
ac92,54hr 50m
ac93,0hr
ac94,12hr 30m
ac95,0hr
ac96,8hr
ac97,21hr
ac98,29hr 16m
ac99,121hr 2m
ad00,15hr 15m
ad01,160hr 19m
ad02,2hr
ad03,32hr
ad04,0hr
ad05,0hr
ad06,0hr
ad07,141hr 22m
ad08,1hr
ad09,0hr
ad10,0hr
ad11,10hr 4m
ad12,7hr 30m
ad13,591hr 27m
ad14,4hr
ad15,8hr 30m
ad16,0hr
ad17,12hr
ad18,0hr
ad19,7hr
ad20,16hr
ad21,0hr
ad22,8hr 42m
ad23,16hr 25m
ad24,0hr
ad25,4hr 30m
ad26,0hr
ad27,1hr 19m
ad28,43hr 40m
ad29,477hr 38m
ad30,304hr 19m
ad31,265hr 53m
ad32,49hr
ad33,4hr
ad34,17hr 37m
ad35,1hr
ad36,13hr 14m
ad37,97hr 40m
ad38,37hr 59m
ad39,13hr 35m
ad40,0hr
ad41,66hr
ad42,0hr
ad43,33hr 50m
ad44,64hr 50m
ad45,4hr
ad46,8hr
ad47,14hr 45m
ad48,51hr 15m
ad49,62hr 13m
ad50,2hr
ad51,0hr
ad52,0hr
ad53,16hr
ad54,3hr
ad55,15hr 22m
ad56,0hr
ad57,21hr
ad58,21hr
ad59,13hr 30m
ad60,69hr
ad61,45hr 15m
ad62,60hr 30m
ad63,116hr 59m
ad64,68hr 2m
ad65,6hr
ad66,0hr
ad67,3hr 49m
ad68,84hr 20m
ad69,0hr
ad70,0hr
ad71,49hr 18m
ad72,310hr 4m
ad73,114hr 55m
ad74,3hr 30m
ad75,59hr 50m
ad76,11hr
ad77,8hr
ad78,0hr
ad79,0hr
ad80,1382hr 15m
ad81,0hr 2m
ad82,3hr
ad83,124hr 48m
ad84,11hr 30m
ad85,15hr
ad86,0hr
ad87,1004hr 20m
ad88,71hr
ad89,26hr 30m
ad90,208hr 37m
ad91,2004hr 21m
ad92,0hr
ad93,48hr 30m
ad94,0hr
ad95,568hr 45m
ad96,13hr
ad97,4hr
ad98,0hr
ad99,0hr
ae00,3hr
ae01,0hr
ae02,16hr
ae03,15hr 58m
ae04,13hr
ae05,73hr 50m
ae06,7hr
ae07,23hr 30m
ae08,21hr
ae09,19hr
ae10,7hr 25m
ae11,105hr 55m
ae12,0hr
ae13,9hr
ae14,28hr 47m
ae15,11hr
ae16,460hr 11m
ae17,20hr
ae18,0hr
ae19,117hr 7m
ae20,97hr 5m
ae21,13hr 5m
ae22,23hr
ae23,63hr
ae24,9hr 47m
ae25,30hr 30m
ae26,5hr
ae27,9hr 30m
ae28,457hr 30m
ae29,0hr
ae30,40hr 44m
ae31,0hr
ae32,7hr 30m
ae33,559hr 14m
ae34,18hr 30m
ae35,3hr
ae36,5hr
ae37,170hr 15m
ae38,11hr 55m
ae39,18hr 30m
ae40,8hr 4m
ae41,0hr
ae42,14hr 30m
ae43,169hr 13m
ae44,337hr 26m
ae45,16hr
ae46,0hr
ae47,1hr
ae48,416hr 10m
ae49,0hr
ae50,0hr
ae51,80hr 59m
ae52,108hr 30m
ae53,0hr
ae54,18hr
ae55,3223hr 4m
ae56,140hr
ae57,38hr 45m
ae58,31hr 28m
ae59,0hr
ae60,125hr 51m
ae61,0hr
ae62,18hr 4m
ae63,35hr 36m
ae64,0hr
ae65,75hr 38m
ae66,2hr 30m
ae67,34hr
ae68,3hr 30m
ae69,27hr
ae70,1hr
ae71,0hr
ae72,18hr 31m
ae73,100hr 59m
ae74,3hr
ae75,0hr
ae76,0hr
ae77,6hr 30m
ae78,87hr 41m
ae79,4hr 30m
ae80,89hr
ae81,0hr
ae82,6hr
ae83,0hr
ae84,30hr 58m
ae85,12hr 9m
ae86,51hr 30m
ae87,89hr 35m
ae88,87hr 30m
ae89,327hr 57m
ae90,0hr
ae91,0hr
ae92,0hr
ae93,12hr
ae94,5hr
ae95,8hr 30m
ae96,0hr
ae97,46hr 10m
ae98,0hr
ae99,1hr
af00,5hr 35m
af01,2hr
af02,0hr
af03,7hr 10m
af04,0hr
af05,0hr
af06,0hr
af07,4hr
af08,0hr
af09,7hr 30m
af10,40hr 10m
af11,42hr 50m
af12,58hr 55m
af13,7hr 5m
af14,4hr 30m
af15,544hr 4m
af16,0hr
af17,3hr
af18,45hr 30m
af19,713hr 40m
af20,0hr
af21,31hr 28m
af22,18hr
af23,0hr
af24,3hr 30m
af25,0hr
af26,0hr
af27,0hr
af28,7hr 30m
af29,0hr
af30,0hr
af31,4hr
af32,0hr
af33,2086hr
af34,15hr
af35,661hr 16m
af36,47hr 21m
af37,22hr 12m
af38,49hr
af39,13hr 30m
af40,3hr 35m
af41,9hr 45m
af42,0hr
af43,4hr
af44,254hr 30m
af45,357hr 13m
af46,12hr 45m
af47,4hr
af48,3hr 49m
af49,0hr"""
list = list.split("\n")
for i in range (len(list)):
    list[i] = list[i].split(",")

for i in range (len(list)):
    list[i][1] = list[i][1].split("hr")
    if list[i][1][1] != '':
        list[i][1][1] = list[i][1][1][:-1]
    else:
        list[i][1][1] = '0'
    list[i][1] = ((int(list[i][1][0]) *60) + int(list[i][1][1]))

# 2147483647 is the max val for the id of role and event, and I made those in the AddLifetimeHours.sql
for i in range (len(list)):
    print ("(\"" + list[i][0] + "\"" + ", " + "100" + ", " + "100" + ", " + "'1999-01-01 00:00:00'" + ", " + "DATE_ADD('1999-01-01 00:00:00', INTERVAL " + str(list[i][1]) + " MINUTE)),")


