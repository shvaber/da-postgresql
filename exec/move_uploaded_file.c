#include <stdlib.h>
#include <stdio.h>
#include <libgen.h>
#include <unistd.h>
#include <pwd.h>
#include <sys/types.h>
#define PATH_SRC "/home/tmp"
#define PATH_DST "/home/tmp/pgsql_restore"
#define ALLOWED_MIME_TYPE "(application/x-gzip|application/zip|text/plain|text/html);"
#define USER "diradmin"
#define GROUP "diradmin"

int main (int argc, const char * argv[])
{
    int ret;
    uid_t ruid, s_ruid;
    gid_t rgid, s_rgid;
    char name[100];
    struct passwd *pw;
    char cmd[255], p_oldname[255], p_newname[255], * b_oldname, * b_newname;

    ruid = getuid();
    rgid = getgid();
    pw = getpwuid(ruid);

    if (ruid == 0) {
        printf("[ERROR] You should not run this programm as %s (UID: %d)! Termninating...\n", pw->pw_name, ruid);
        return 0;
    }
     if (strcmp(pw->pw_name, USER) != 0) {
        printf("[ERROR] You are not allowed to run this programm as %s (UID: %d)! User %s is expected. Terminating...\n", pw->pw_name, ruid, USER);
        return 0;
    }

    if (argc < 3){
        printf("usage: %s [old_name.ext] [new_name.ext]\n", argv[0]);
        return 0;
    }

    char *oldname = (char *)argv[1]; //basename(argv[1]);
    char *newname = (char *)argv[2]; //basename(argv[2]);

    b_oldname = basename(oldname);
    b_newname = basename(newname);

    sprintf(p_oldname, "%s/%s", PATH_SRC, b_oldname);
    sprintf(p_newname, "%s/%s", PATH_DST, b_newname);

    setuid(0);
    setgid(0);

    if( access(p_oldname, F_OK ) != -1 ) {
        // file exists
        printf("[OK] File %s exists!\n", p_oldname);
    } else {
        // file doesn't exist
        printf("[ERROR] File %s does not exist! Terminating...\n", p_oldname);
        return(1);
    }

    sprintf(cmd, "file -i '%s' | egrep -c '%s' > /dev/null", p_oldname, ALLOWED_MIME_TYPE);
    ret = system(cmd);

    if(ret == 0)
    {
        printf("[OK] File %s seems to be of a expected mime type!\n",p_oldname);
    }
    else
    {
        printf("[ERROR] File %s is of wrong mime type! Terminating...\n", p_oldname);
        sprintf(cmd, "file -i '%s'", p_oldname);
        system(cmd);
        return(1);
    }

    printf("[OK] Moving file from %s to %s\n",p_oldname,p_newname);

    ret = rename(p_oldname, p_newname);

    if(ret == 0)
    {
        printf("[OK] File %s renamed successfully to %s\n",p_oldname,p_newname);
        chown(p_newname,ruid,rgid);
        chmod(p_newname, 0600);
    }
    else
    {
        printf("[ERROR] Unable to rename the file\n");
        return(1);
    }

    return(0);
}

